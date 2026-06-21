<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run in dependency order — globalization tables first.
     */
    public function run(): void
    {
        $this->call([
            // ── Globalization (no dependencies) ───────────────────────────
            CountrySeeder::class,
            CurrencySeeder::class,
            TimezoneSeeder::class,

            // ── Business Reference Data ───────────────────────────────────
            SubscriptionPlanSeeder::class,

            // ── Tenant & Identity Layer ───────────────────────────────────
            TenantIdentitySeeder::class,

            // ── Tenant Reference Data (must run after tenants exist) ───────
            AssetCategorySeeder::class,
        ]);
    }
}
