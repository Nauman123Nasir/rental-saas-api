<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/v1/users
     * Paginated list of users belonging to the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $query = User::where('tenant_id', $tenantId)->with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role_id')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $request->role_id));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->integer('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    /**
     * POST /api/v1/users
     * Create a new user and assign roles.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'status'     => ['nullable', 'string', 'in:active,inactive,suspended'],
            'branch_id'  => ['nullable', 'integer', 'exists:branches,id'],
            'role_ids'   => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $tenantId = Auth::guard('api')->user()->tenant_id;

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'tenant_id' => $tenantId,
            'branch_id' => $validated['branch_id'] ?? null,
            'status'    => $validated['status'] ?? 'active',
        ]);

        if (!empty($validated['role_ids'])) {
            $validRoleIds = Role::where('tenant_id', $tenantId)
                ->whereIn('id', $validated['role_ids'])
                ->pluck('id')
                ->toArray();
            $user->roles()->sync($validRoleIds);
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => $user,
        ], 201);
    }

    /**
     * GET /api/v1/users/{id}
     * Show a single user with roles and branch.
     */
    public function show(int $id): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $user = User::where('tenant_id', $tenantId)
            ->with(['roles.permissions', 'branch'])
            ->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * PUT /api/v1/users/{id}
     * Update user details and sync roles.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = Auth::guard('api')->user()->tenant_id;

        $user = User::where('tenant_id', $tenantId)->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password'   => ['nullable', 'string', 'min:8'],
            'status'     => ['nullable', 'string', 'in:active,inactive,suspended'],
            'branch_id'  => ['nullable', 'integer', 'exists:branches,id'],
            'role_ids'   => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $updateData = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'branch_id' => $validated['branch_id'] ?? null,
            'status'    => $validated['status'] ?? $user->status,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if (isset($validated['role_ids'])) {
            $validRoleIds = Role::where('tenant_id', $tenantId)
                ->whereIn('id', $validated['role_ids'])
                ->pluck('id')
                ->toArray();
            $user->roles()->sync($validRoleIds);
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => $user,
        ]);
    }

    /**
     * DELETE /api/v1/users/{id}
     * Delete a user (cannot delete own account).
     */
    public function destroy(int $id): JsonResponse
    {
        $authUser = Auth::guard('api')->user();

        if ($authUser->id === $id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user = User::where('tenant_id', $authUser->tenant_id)->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }
}
