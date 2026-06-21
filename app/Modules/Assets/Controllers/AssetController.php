<?php

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query()->with(['category', 'maintenanceBlocks']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('vin_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $assets = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:asset_categories,id',
            'asset_code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'vin_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'status' => 'required|in:Available,Reserved,Rented,Maintenance,Inactive,Retired',
            'ownership_type' => 'nullable|string|max:255',
            'current_mileage' => 'nullable|integer',
            'current_hours' => 'nullable|numeric',
            'fuel_type' => 'nullable|string|max:255',
            'transmission' => 'nullable|string|max:255',
            'daily_rate' => 'required|numeric',
            'weekly_rate' => 'required|numeric',
            'monthly_rate' => 'required|numeric',
            'hourly_rate' => 'required|numeric',
            'maintenance_blocks' => 'nullable|array',
            'maintenance_blocks.*.start_datetime' => 'required_with:maintenance_blocks|date',
            'maintenance_blocks.*.end_datetime' => 'required_with:maintenance_blocks|date|after_or_equal:maintenance_blocks.*.start_datetime',
            'maintenance_blocks.*.reason' => 'nullable|string',
            'maintenance_blocks.*.cost' => 'required_with:maintenance_blocks|numeric',
        ]);

        // Auto-generate asset_code if not provided
        if (empty($validated['asset_code'])) {
            $tenantId = auth()->user()->tenant_id;
            do {
                $count = Asset::where('tenant_id', $tenantId)->withTrashed()->count() + 1;
                $validated['asset_code'] = 'AST-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            } while (Asset::where('tenant_id', $tenantId)->where('asset_code', $validated['asset_code'])->exists());
        }

        DB::beginTransaction();

        try {
            $asset = Asset::create($validated);

            if (!empty($validated['maintenance_blocks'])) {
                foreach ($validated['maintenance_blocks'] as $block) {
                    $asset->blocks()->create([
                        'tenant_id' => $asset->tenant_id,
                        'block_type' => 'Maintenance',
                        'start_datetime' => $block['start_datetime'],
                        'end_datetime' => $block['end_datetime'],
                        'reason' => $block['reason'] ?? null,
                        'cost' => $block['cost'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset created successfully.',
                'data' => $asset->load(['category', 'maintenanceBlocks'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create asset.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $asset = Asset::with(['category', 'maintenanceBlocks'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $asset
        ]);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:asset_categories,id',
            'asset_code' => 'sometimes|required|string|max:255',
            'name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'vin_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:Available,Reserved,Rented,Maintenance,Inactive,Retired',
            'ownership_type' => 'nullable|string|max:255',
            'current_mileage' => 'nullable|integer',
            'current_hours' => 'nullable|numeric',
            'fuel_type' => 'nullable|string|max:255',
            'transmission' => 'nullable|string|max:255',
            'daily_rate' => 'sometimes|required|numeric',
            'weekly_rate' => 'sometimes|required|numeric',
            'monthly_rate' => 'sometimes|required|numeric',
            'hourly_rate' => 'sometimes|required|numeric',
            'maintenance_blocks' => 'nullable|array',
            'maintenance_blocks.*.id' => 'nullable|exists:asset_blocks,id',
            'maintenance_blocks.*.start_datetime' => 'required_with:maintenance_blocks|date',
            'maintenance_blocks.*.end_datetime' => 'required_with:maintenance_blocks|date|after_or_equal:maintenance_blocks.*.start_datetime',
            'maintenance_blocks.*.reason' => 'nullable|string',
            'maintenance_blocks.*.cost' => 'required_with:maintenance_blocks|numeric',
        ]);

        DB::beginTransaction();

        try {
            $asset->update($validated);

            if (isset($validated['maintenance_blocks'])) {
                $providedBlockIds = collect($validated['maintenance_blocks'])->pluck('id')->filter()->all();
                
                // Delete maintenance blocks that are missing from the request
                $asset->maintenanceBlocks()->whereNotIn('id', $providedBlockIds)->delete();

                // Update or Create
                foreach ($validated['maintenance_blocks'] as $blockData) {
                    if (isset($blockData['id'])) {
                        AssetBlock::where('id', $blockData['id'])
                            ->where('asset_id', $asset->id)
                            ->update([
                                'start_datetime' => $blockData['start_datetime'],
                                'end_datetime' => $blockData['end_datetime'],
                                'reason' => $blockData['reason'] ?? null,
                                'cost' => $blockData['cost'],
                            ]);
                    } else {
                        $asset->blocks()->create([
                            'tenant_id' => $asset->tenant_id,
                            'block_type' => 'Maintenance',
                            'start_datetime' => $blockData['start_datetime'],
                            'end_datetime' => $blockData['end_datetime'],
                            'reason' => $blockData['reason'] ?? null,
                            'cost' => $blockData['cost'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully.',
                'data' => $asset->fresh(['category', 'maintenanceBlocks'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully.'
        ]);
    }
}
