<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategories = ['Cars', 'SUV', 'Vans', 'Trucks', 'Equipment'];

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($defaultCategories as $name) {
                $exists = DB::table('asset_categories')
                    ->where('tenant_id', $tenantId)
                    ->where('name', $name)
                    ->exists();

                if (!$exists) {
                    DB::table('asset_categories')->insert([
                        'tenant_id'  => $tenantId,
                        'name'       => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
