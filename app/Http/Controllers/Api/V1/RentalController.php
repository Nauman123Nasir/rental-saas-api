<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Asset;
use App\Models\Invoice;
use App\Models\RentalPickupInspection;
use App\Models\RentalReturnInspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RentalController extends Controller
{
    public function index()
    {
        $rentals = Rental::with(['customer', 'asset', 'pickupInspection', 'returnInspection'])->get();
        return response()->json($rentals);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'fuel_level' => 'required|numeric',
            'odometer_reading' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            $asset = $reservation->assets()->first()->asset; // assume 1 asset

            $rental = Rental::create([
                'rental_no' => 'RNT-' . strtoupper(uniqid()),
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'asset_id' => $asset->id,
                'pickup_datetime_utc' => Carbon::now(),
                'expected_return_datetime_utc' => $reservation->return_datetime_utc,
                'status' => 'Active',
                'tenant_id' => $reservation->tenant_id,
            ]);

            RentalPickupInspection::create([
                'rental_id' => $rental->id,
                'inspection_date' => Carbon::now(),
                'fuel_level' => $request->fuel_level,
                'odometer_reading' => $request->odometer_reading,
                'notes' => $request->notes,
                'inspected_by' => auth()->id() ?? 1,
            ]);

            $reservation->update(['status' => 'Converted']);
            $asset->update(['status' => 'Rented']);

            DB::commit();
            return response()->json($rental->load(['customer', 'asset', 'pickupInspection']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkin(Request $request, $id)
    {
        $request->validate([
            'fuel_level' => 'required|numeric',
            'odometer_reading' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $rental = Rental::with(['charges', 'reservation'])->findOrFail($id);

            RentalReturnInspection::create([
                'rental_id' => $rental->id,
                'inspection_date' => Carbon::now(),
                'fuel_level' => $request->fuel_level,
                'odometer_reading' => $request->odometer_reading,
                'notes' => $request->notes,
                'inspected_by' => auth()->id() ?? 1,
            ]);

            $rental->update([
                'actual_return_datetime_utc' => Carbon::now(),
                'status' => 'Returned',
            ]);

            Asset::where('id', $rental->asset_id)->update(['status' => 'Available']);

            // Auto-generate invoice on check-in if one doesn't already exist
            if (!$rental->invoice()->exists()) {
                $lines = [];

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

                $invoiceNo = 'INV-' . strtoupper(str_pad($rental->tenant_id, 3, '0', STR_PAD_LEFT))
                    . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'tenant_id'       => $rental->tenant_id,
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
                    'issue_date'      => now()->toDateString(),
                    'due_date'        => null,
                ]);

                foreach ($lines as $line) {
                    $invoice->lines()->create($line);
                }
            }

            DB::commit();
            return response()->json($rental->load(['returnInspection', 'invoice.lines']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $rental = Rental::with(['customer', 'asset', 'pickupInspection', 'returnInspection', 'charges'])->findOrFail($id);
        return response()->json($rental);
    }
}
