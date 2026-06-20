<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantIdentitySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch Subscription Plan
        $planId = DB::table('subscription_plans')->where('name', 'Professional')->value('id') 
            ?? DB::table('subscription_plans')->first()->id;

        // 2. Fetch Globalization Data
        $countryId = DB::table('countries')->where('iso2', 'US')->value('id') 
            ?? DB::table('countries')->first()->id;
        
        $currencyId = DB::table('currencies')->where('code', 'USD')->value('id') 
            ?? DB::table('currencies')->first()->id;

        $timezoneId = DB::table('timezones')->where('name', 'like', '%New_York%')->value('id') 
            ?? DB::table('timezones')->first()->id;

        // 3. Create Tenant
        $tenantId = DB::table('tenants')->insertGetId([
            'uuid'                 => (string) Str::uuid(),
            'name'                 => 'Acme Rent-A-Car',
            'status'               => 'active',
            'subscription_plan_id' => $planId,
            'currency_id'          => $currencyId,
            'timezone_id'          => $timezoneId,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // 4. Create Branch
        $branchId = DB::table('branches')->insertGetId([
            'tenant_id'   => $tenantId,
            'name'        => 'Acme Downtown Branch',
            'code'        => 'ACME-DT',
            'country_id'  => $countryId,
            'timezone_id' => $timezoneId,
            'currency_id' => $currencyId,
            'address'     => '123 Main Street',
            'city'        => 'New York',
            'state'       => 'NY',
            'postal_code' => '10001',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 5. Seed Permissions (Global)
        $modules = ['customers', 'assets', 'reservations', 'rentals', 'finance', 'users'];
        $actions = ['*', 'view', 'create', 'update', 'delete'];

        $permissionIds = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                // Ensure duplicate entries are ignored
                $id = DB::table('permissions')->insertGetId([
                    'module'      => $module,
                    'action'      => $action,
                    'description' => "Allows " . ($action === '*' ? 'all' : $action) . " actions on " . $module,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $permissionIds["{$module}.{$action}"] = $id;
            }
        }

        // 6. Create Roles (Tenant-scoped)
        $superAdminRoleId = DB::table('roles')->insertGetId([
            'tenant_id'   => $tenantId,
            'name'        => 'Super Admin',
            'description' => 'System Super Administrator with all permissions',
            'is_system'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $agentRoleId = DB::table('roles')->insertGetId([
            'tenant_id'   => $tenantId,
            'name'        => 'Agent',
            'description' => 'Operational Agent with limited permissions',
            'is_system'   => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 7. Associate Permissions to Roles
        // Super Admin gets all permissions
        $superAdminRolePerms = [];
        foreach ($permissionIds as $pId) {
            $superAdminRolePerms[] = [
                'role_id'       => $superAdminRoleId,
                'permission_id' => $pId,
            ];
        }
        DB::table('role_permissions')->insertOrIgnore($superAdminRolePerms);

        // Agent gets specific operational permissions
        $agentPermKeys = [
            'customers.view', 'customers.create', 'customers.update',
            'assets.view',
            'reservations.view', 'reservations.create', 'reservations.update',
            'rentals.view', 'rentals.create', 'rentals.update',
        ];
        $agentRolePerms = [];
        foreach ($agentPermKeys as $key) {
            if (isset($permissionIds[$key])) {
                $agentRolePerms[] = [
                    'role_id'       => $agentRoleId,
                    'permission_id' => $permissionIds[$key],
                ];
            }
        }
        DB::table('role_permissions')->insertOrIgnore($agentRolePerms);

        // 8. Create Test Users
        $adminUserId = DB::table('users')->insertGetId([
            'tenant_id'  => $tenantId,
            'branch_id'  => $branchId,
            'name'       => 'Acme Admin',
            'email'      => 'admin@acmerental.com',
            'password'   => Hash::make('password'),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $agentUserId = DB::table('users')->insertGetId([
            'tenant_id'  => $tenantId,
            'branch_id'  => $branchId,
            'name'       => 'Acme Agent',
            'email'      => 'agent@acmerental.com',
            'password'   => Hash::make('password'),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 9. Assign Roles to Users
        DB::table('user_roles')->insertOrIgnore([
            [
                'user_id' => $adminUserId,
                'role_id' => $superAdminRoleId,
            ],
            [
                'user_id' => $agentUserId,
                'role_id' => $agentRoleId,
            ],
        ]);
    }
}
