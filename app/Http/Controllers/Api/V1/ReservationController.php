<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Asset;
use App\Models\AssetBlock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with(['assets', 'customer'])->get();
        return response()->json($reservations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'assets' => 'required|array',
            'assets.*' => 'exists:assets,id',
            'pickup_datetime_utc' => 'required|date',
            'return_datetime_utc' => 'required|date|after:pickup_datetime_utc',
        ]);

        $pickup = Carbon::parse($request->pickup_datetime_utc);
        $return = Carbon::parse($request->return_datetime_utc);

        // Check availability — ignore stale reservation blocks
        foreach ($request->assets as $assetId) {
            $conflicts = AssetBlock::where('asset_id', $assetId)
                ->where(function ($query) use ($pickup, $return) {
                    $query->whereBetween('start_datetime', [$pickup, $return])
                          ->orWhereBetween('end_datetime', [$pickup, $return])
                          ->orWhere(function ($q) use ($pickup, $return) {
                              $q->where('start_datetime', '<=', $pickup)
                                ->where('end_datetime', '>=', $return);
                          });
                })
                ->where(function ($q) {
                    // Non-reservation blocks (manual) always count.
                    // Reservation blocks count only when NO completed rental exists for them.
                    $q->where('block_type', '!=', 'Reservation')
                      ->orWhere(function ($rb) {
                          $rb->where('block_type', 'Reservation')
                             ->whereNotExists(function ($sub) {
                                 // A block is stale when a returned/cancelled rental matches it.
                                 // New-style blocks: match via reservation_id = reference_id.
                                 // Legacy blocks (reference_id IS NULL): match via asset_id
                                 // and expected_return_datetime_utc = block end_datetime.
                                 $sub->select(DB::raw(1))
                                     ->from('rentals')
                                     ->whereIn('rentals.status', ['Returned', 'Cancelled'])
                                     ->where(function ($match) {
                                         $match->where(function ($byRef) {
                                             $byRef->whereNotNull('asset_blocks.reference_id')
                                                   ->whereColumn('rentals.reservation_id', 'asset_blocks.reference_id');
                                         })->orWhere(function ($byDate) {
                                             $byDate->whereNull('asset_blocks.reference_id')
                                                    ->whereColumn('rentals.asset_id', 'asset_blocks.asset_id')
                                                    ->whereColumn('rentals.expected_return_datetime_utc', 'asset_blocks.end_datetime');
                                         });
                                     });
                             });
                      });
                })
                ->exists();

            if ($conflicts) {
                return response()->json(['message' => 'Asset ' . $assetId . ' is not available for the selected dates.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $reservation = Reservation::create([
                'reservation_no' => 'RES-' . strtoupper(uniqid()),
                'customer_id' => $request->customer_id,
                'status' => 'Pending',
                'pickup_datetime_utc' => $pickup,
                'return_datetime_utc' => $return,
                'tenant_id' => auth()->user()->tenant_id ?? 1, // fallback for now
            ]);

            foreach ($request->assets as $assetId) {
                $reservation->assets()->create(['asset_id' => $assetId]);

                AssetBlock::create([
                    'asset_id'       => $assetId,
                    'block_type'     => 'Reservation',
                    'start_datetime' => $pickup,
                    'end_datetime'   => $return,
                    'reason'         => 'Blocked for Reservation: ' . $reservation->reservation_no,
                    'tenant_id'      => $reservation->tenant_id,
                    'reference_type' => 'reservation',
                    'reference_id'   => $reservation->id,
                ]);

                Asset::where('id', $assetId)->update(['status' => 'Reserved']);
            }

            DB::commit();
            return response()->json($reservation->load('assets'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating reservation', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $reservation = Reservation::with(['assets', 'customer'])->findOrFail($id);
        return response()->json($reservation);
    }

    public function update(Request $request, string $id)
    {
        // Add logic as needed
        return response()->json(['message' => 'Not implemented yet']);
    }

    public function destroy(string $id)
    {
        // Add logic as needed
        return response()->json(['message' => 'Not implemented yet']);
    }
}
