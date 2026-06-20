<?php

namespace App\Modules\Finance\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * List all invoices for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Invoice::with(['customer', 'rental'])
            ->where('tenant_id', $user->tenant_id)
            ->orderByDesc('created_at');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Show a single invoice with its lines and payments.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $invoice = Invoice::with(['customer', 'rental', 'lines', 'payments.receipt'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return response()->json($invoice);
    }

    /**
     * Generate a new invoice from a closed rental.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'rental_id'     => 'required|exists:rentals,id',
            'issue_date'    => 'nullable|date',
            'due_date'      => 'nullable|date',
            'notes'         => 'nullable|string',
        ]);

        $user   = Auth::user();
        $rental = Rental::with(['charges', 'reservation'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($request->rental_id);

        // Prevent duplicate invoices per rental
        if ($rental->invoice()->exists()) {
            return response()->json([
                'message' => 'An invoice already exists for this rental.',
                'invoice' => $rental->invoice,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate subtotal from rental charges
            $lines = [];

            // Base rental line
            $baseAmount = $rental->reservation?->total_amount ?? 0;
            if ($baseAmount > 0) {
                $lines[] = [
                    'description' => 'Base Rental Charge',
                    'line_type'   => 'rental_base',
                    'unit_price'  => $baseAmount,
                    'quantity'    => 1,
                    'total'       => $baseAmount,
                ];
            }

            // Additional charges from operations (fuel, damage, late fees, etc.)
            foreach ($rental->charges as $charge) {
                $lines[] = [
                    'description' => $charge->description ?? ucfirst(str_replace('_', ' ', $charge->charge_type)),
                    'line_type'   => $charge->charge_type,
                    'unit_price'  => $charge->amount,
                    'quantity'    => 1,
                    'total'       => $charge->amount,
                ];
            }

            $subtotal = collect($lines)->sum('total');

            $invoiceNo = 'INV-' . strtoupper(str_pad($user->tenant_id, 3, '0', STR_PAD_LEFT))
                . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'tenant_id'       => $user->tenant_id,
                'rental_id'       => $rental->id,
                'customer_id'     => $rental->customer_id,
                'invoice_no'      => $invoiceNo,
                'status'          => 'Issued',
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'total_amount'    => $subtotal,
                'amount_paid'     => 0,
                'balance_due'     => $subtotal,
                'currency_code'   => 'USD',
                'issue_date'      => $request->issue_date ?? now()->toDateString(),
                'due_date'        => $request->due_date,
                'notes'           => $request->notes,
            ]);

            // Create invoice lines
            foreach ($lines as $line) {
                $invoice->lines()->create($line);
            }

            DB::commit();

            return response()->json(
                $invoice->load(['lines', 'customer', 'rental']),
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate invoice.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Void/cancel an invoice.
     */
    public function void(int $id): JsonResponse
    {
        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($id);

        if (in_array($invoice->status, ['Paid', 'Void'])) {
            return response()->json(['message' => 'Cannot void a Paid or already Voided invoice.'], 422);
        }

        $invoice->update(['status' => 'Void']);

        return response()->json(['message' => 'Invoice voided successfully.', 'invoice' => $invoice]);
    }
}
