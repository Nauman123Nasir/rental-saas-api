<?php

namespace App\Modules\Vehicles\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::query()->with('maintenanceLogs');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $vehicles = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer',
            'license_plate' => 'required|string|max:255',
            'vin' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:available,rented,maintenance,retired',
            'mileage' => 'required|integer',
            'daily_rate' => 'required|numeric',
            'weekly_rate' => 'required|numeric',
            'monthly_rate' => 'required|numeric',
            'hourly_rate' => 'required|numeric',
            'maintenance_logs' => 'nullable|array',
            'maintenance_logs.*.date' => 'required_with:maintenance_logs|date',
            'maintenance_logs.*.description' => 'required_with:maintenance_logs|string',
            'maintenance_logs.*.cost' => 'required_with:maintenance_logs|numeric',
        ]);

        DB::beginTransaction();

        try {
            $vehicle = Vehicle::create($validated);

            if (!empty($validated['maintenance_logs'])) {
                foreach ($validated['maintenance_logs'] as $log) {
                    $vehicle->maintenanceLogs()->create($log);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully.',
                'data' => $vehicle->load('maintenanceLogs')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create vehicle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $vehicle = Vehicle::with('maintenanceLogs')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $vehicle
        ]);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'make' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer',
            'license_plate' => 'sometimes|required|string|max:255',
            'vin' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:available,rented,maintenance,retired',
            'mileage' => 'sometimes|required|integer',
            'daily_rate' => 'sometimes|required|numeric',
            'weekly_rate' => 'sometimes|required|numeric',
            'monthly_rate' => 'sometimes|required|numeric',
            'hourly_rate' => 'sometimes|required|numeric',
            'maintenance_logs' => 'nullable|array',
            'maintenance_logs.*.id' => 'nullable|exists:maintenance_logs,id',
            'maintenance_logs.*.date' => 'required_with:maintenance_logs|date',
            'maintenance_logs.*.description' => 'required_with:maintenance_logs|string',
            'maintenance_logs.*.cost' => 'required_with:maintenance_logs|numeric',
        ]);

        DB::beginTransaction();

        try {
            $vehicle->update($validated);

            if (isset($validated['maintenance_logs'])) {
                $providedLogIds = collect($validated['maintenance_logs'])->pluck('id')->filter()->all();
                
                // Delete logs that are missing from the request
                $vehicle->maintenanceLogs()->whereNotIn('id', $providedLogIds)->delete();

                // Update or Create
                foreach ($validated['maintenance_logs'] as $logData) {
                    if (isset($logData['id'])) {
                        MaintenanceLog::where('id', $logData['id'])
                            ->where('vehicle_id', $vehicle->id)
                            ->update([
                                'date' => $logData['date'],
                                'description' => $logData['description'],
                                'cost' => $logData['cost'],
                            ]);
                    } else {
                        $vehicle->maintenanceLogs()->create($logData);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully.',
                'data' => $vehicle->fresh('maintenanceLogs')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully.'
        ]);
    }
}
