<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Asset;
use App\Models\AssetBlock;
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

            // Remove the asset block so the asset can be re-booked immediately.
            // Match by reference_id (blocks created after the fix) or by exact
            // date range (blocks created before reference_id was stored).
            AssetBlock::where('asset_id', $rental->asset_id)
                ->where('block_type', 'Reservation')
                ->where(function ($q) use ($rental) {
                    $q->where('reference_id', $rental->reservation_id);
                    if ($rental->reservation) {
                        $q->orWhere(function ($inner) use ($rental) {
                            $inner->where('start_datetime', $rental->reservation->pickup_datetime_utc)
                                  ->where('end_datetime',   $rental->reservation->return_datetime_utc);
                        });
                    }
                })
                ->delete();

            // Auto-generate invoice on check-in.
            // If an invoice already exists but has $0 (created before the rate-
            // calculation fix), delete it and regenerate with the correct amount.
            $existingInvoice = $rental->invoice()->first();
            $shouldGenerate  = !$existingInvoice || (float) $existingInvoice->total_amount === 0.0;

            if ($shouldGenerate) {
                if ($existingInvoice) {
                    $existingInvoice->lines()->delete();
                    $existingInvoice->delete();
                }

                $lines = [];

                // Derive charge from asset rate × actual rental duration.
                // reservation.total_amount is never populated during booking.
                $baseAmount  = 0;
                $description = 'Base Rental Charge';

                // Use withoutGlobalScopes to avoid any tenant-scope mismatch
                $asset = Asset::withoutGlobalScopes()->find($rental->asset_id);
                if ($asset) {
                    $pickup     = $rental->pickup_datetime_utc ?? Carbon::now();
                    $returnedAt = $rental->actual_return_datetime_utc ?? Carbon::now();
                    $minutes    = max(1, (int) $pickup->diffInMinutes($returnedAt));

                    if ((float) $asset->daily_rate > 0) {
                        $days        = max(1, (int) ceil($minutes / 1440));
                        $baseAmount  = round((float) $asset->daily_rate * $days, 2);
                        $description = "Base Rental Charge ({$days} day" . ($days > 1 ? 's' : '') . " @ {$asset->daily_rate}/day)";
                    } elseif ((float) $asset->hourly_rate > 0) {
                        $hours       = max(1, (int) ceil($minutes / 60));
                        $baseAmount  = round((float) $asset->hourly_rate * $hours, 2);
                        $description = "Base Rental Charge ({$hours} hr" . ($hours > 1 ? 's' : '') . " @ {$asset->hourly_rate}/hr)";
                    } elseif ((float) $asset->weekly_rate > 0) {
                        $weeks       = max(1, (int) ceil($minutes / (1440 * 7)));
                        $baseAmount  = round((float) $asset->weekly_rate * $weeks, 2);
                        $description = "Base Rental Charge ({$weeks} wk" . ($weeks > 1 ? 's' : '') . " @ {$asset->weekly_rate}/wk)";
                    } elseif ((float) $asset->monthly_rate > 0) {
                        $months      = max(1, (int) ceil($minutes / (1440 * 30)));
                        $baseAmount  = round((float) $asset->monthly_rate * $months, 2);
                        $description = "Base Rental Charge ({$months} mo" . ($months > 1 ? 's' : '') . " @ {$asset->monthly_rate}/mo)";
                    }
                }

                if ($baseAmount > 0) {
                    $lines[] = [
                        'description' => $description,
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
