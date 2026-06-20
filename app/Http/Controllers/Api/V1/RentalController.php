<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Reservation;
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
            $rental = Rental::findOrFail($id);

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

            DB::commit();
            return response()->json($rental->load(['returnInspection']));
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
