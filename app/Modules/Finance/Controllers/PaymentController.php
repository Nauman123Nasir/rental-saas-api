<?php

namespace App\Modules\Finance\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List all payments for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $payments = Payment::with(['invoice', 'customer', 'receipt'])
            ->where('tenant_id', $user->tenant_id)
            ->when($request->filled('invoice_id'), fn ($q) => $q->where('invoice_id', $request->invoice_id))
            ->orderByDesc('payment_datetime_utc')
            ->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Show a single payment with its receipt.
     */
    public function show(int $id): JsonResponse
    {
        $user    = Auth::user();
        $payment = Payment::with(['invoice', 'customer', 'receipt'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Record a new payment against an invoice and auto-generate a receipt.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id'            => 'required|exists:invoices,id',
            'amount'                => 'required|numeric|min:0.01',
            'payment_method'        => 'required|in:cash,card,bank_transfer,cheque,online',
            'payment_datetime_utc'  => 'nullable|date',
            'reference_no'          => 'nullable|string|max:100',
            'notes'                 => 'nullable|string',
        ]);

        $user    = Auth::user();
        $invoice = Invoice::where('tenant_id', $user->tenant_id)->findOrFail($request->invoice_id);

        if ($invoice->status === 'Void') {
            return response()->json(['message' => 'Cannot record payment against a Voided invoice.'], 422);
        }

        if ($invoice->status === 'Paid') {
            return response()->json(['message' => 'Invoice is already fully paid.'], 422);
        }

        DB::beginTransaction();
        try {
            $paymentNo = 'PAY-' . strtoupper(str_pad($user->tenant_id, 3, '0', STR_PAD_LEFT))
                . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'tenant_id'             => $user->tenant_id,
                'invoice_id'            => $invoice->id,
                'customer_id'           => $invoice->customer_id,
                'payment_no'            => $paymentNo,
                'payment_method'        => $request->payment_method,
                'amount'                => $request->amount,
                'currency_code'         => $invoice->currency_code,
                'payment_datetime_utc'  => $request->payment_datetime_utc ?? now(),
                'reference_no'          => $request->reference_no,
                'notes'                 => $request->notes,
            ]);

            // Auto-generate receipt
            $receiptNo = 'REC-' . strtoupper(str_pad($user->tenant_id, 3, '0', STR_PAD_LEFT))
                . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $receipt = Receipt::create([
                'tenant_id'     => $user->tenant_id,
                'payment_id'    => $payment->id,
                'invoice_id'    => $invoice->id,
                'customer_id'   => $invoice->customer_id,
                'receipt_no'    => $receiptNo,
                'amount'        => $payment->amount,
                'currency_code' => $payment->currency_code,
                'issued_at'     => now(),
            ]);

            // Recalculate invoice balance
            $invoice->recalculateBalance();

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded and receipt generated.',
                'payment' => $payment->load('receipt'),
                'invoice' => $invoice->fresh(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record payment.', 'error' => $e->getMessage()], 500);
        }
    }
}
