<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * GET /api/v1/roles
     * List all roles for the tenant with user counts and permissions.
     */
    public function index(): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $roles = Role::where('tenant_id', $tenantId)
            ->withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $roles]);
    }

    /**
     * POST /api/v1/roles
     * Create a new tenant-scoped role and assign permissions.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:255'],
            'permission_ids'     => ['nullable', 'array'],
            'permission_ids.*'   => ['integer', 'exists:permissions,id'],
        ]);

        $tenantId = Auth::guard('api')->user()->tenant_id;

        $role = Role::create([
            'tenant_id'   => $tenantId,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_system'   => false,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data'    => $role,
        ], 201);
    }

    /**
     * GET /api/v1/roles/{id}
     * Show a single role with its permissions.
     */
    public function show(int $id): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $role = Role::where('tenant_id', $tenantId)->with('permissions')->find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $role]);
    }

    /**
     * PUT /api/v1/roles/{id}
     * Update a role's name, description and sync permissions.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $role = Role::where('tenant_id', $tenantId)->find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:255'],
            'permission_ids'     => ['nullable', 'array'],
            'permission_ids.*'   => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? $role->description,
        ]);

        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data'    => $role,
        ]);
    }

    /**
     * DELETE /api/v1/roles/{id}
     * Delete a role (system roles are protected).
     */
    public function destroy(int $id): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $role = Role::where('tenant_id', $tenantId)->find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 422);
        }

        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
    }
}
