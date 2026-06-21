<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * GET /api/v1/permissions
     * Returns all permissions grouped by module — used by the role form.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('module')->orderBy('action')->get();

        $grouped = $permissions->groupBy('module')->map(function ($perms, $module) {
            return [
                'module'      => $module,
                'permissions' => $perms->values(),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $grouped]);
    }
}
