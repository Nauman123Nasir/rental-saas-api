<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds roles.* permissions and assigns them to the Super Admin role.
 * Run this on existing databases: php artisan db:seed --class=RolesPermissionsSeeder
 */
class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $actions = ['*', 'view', 'create', 'update', 'delete'];
        $newPermIds = [];

        foreach ($actions as $action) {
            $existing = DB::table('permissions')
                ->where('module', 'roles')
                ->where('action', $action)
                ->first();

            if ($existing) {
                $newPermIds[] = $existing->id;
                continue;
            }

            $newPermIds[] = DB::table('permissions')->insertGetId([
                'module'      => 'roles',
                'action'      => $action,
                'description' => 'Allows ' . ($action === '*' ? 'all' : $action) . ' actions on roles',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Assign all roles.* permissions to every Super Admin role across all tenants
        $superAdminRoles = DB::table('roles')->where('is_system', true)->pluck('id');

        foreach ($superAdminRoles as $roleId) {
            foreach ($newPermIds as $permId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }
}
