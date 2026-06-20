<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'Starter',
                'monthly_price' => 49.00,
                'annual_price'  => 490.00,
                'features_json' => json_encode([
                    'max_branches'   => 1,
                    'max_assets'     => 50,
                    'max_users'      => 5,
                    'customer_docs'  => true,
                    'basic_reports'  => true,
                    'api_access'     => false,
                ]),
                'is_active' => true,
            ],
            [
                'name'          => 'Professional',
                'monthly_price' => 149.00,
                'annual_price'  => 1490.00,
                'features_json' => json_encode([
                    'max_branches'   => 5,
                    'max_assets'     => 250,
                    'max_users'      => 25,
                    'customer_docs'  => true,
                    'basic_reports'  => true,
                    'advanced_reports' => true,
                    'api_access'     => true,
                ]),
                'is_active' => true,
            ],
            [
                'name'          => 'Enterprise',
                'monthly_price' => 399.00,
                'annual_price'  => 3990.00,
                'features_json' => json_encode([
                    'max_branches'   => -1,    // unlimited
                    'max_assets'     => -1,    // unlimited
                    'max_users'      => -1,    // unlimited
                    'customer_docs'  => true,
                    'basic_reports'  => true,
                    'advanced_reports' => true,
                    'api_access'     => true,
                    'white_label'    => true,
                    'dedicated_support' => true,
                ]),
                'is_active' => true,
            ],
        ];

        $now = now();
        foreach ($plans as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }

        DB::table('subscription_plans')->insertOrIgnore($plans);
    }
}
