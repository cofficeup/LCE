<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Monthly plans
            [
                'name' => '1 Bag Monthly',
                'billing_cycle' => 'monthly',
                'bags_per_month' => 1,
                'price' => 39.99,
                'bag_overage_rate' => 39.99, // Same as monthly price
                'price_per_lb_overage' => 2.99, // PPO rate
                'is_active' => true,
            ],
            [
                'name' => '2 Bags Monthly',
                'billing_cycle' => 'monthly',
                'bags_per_month' => 2,
                'price' => 74.99,
                'bag_overage_rate' => 37.50,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
            [
                'name' => '4 Bags Monthly',
                'billing_cycle' => 'monthly',
                'bags_per_month' => 4,
                'price' => 139.99,
                'bag_overage_rate' => 35.00,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
            [
                'name' => '8 Bags Monthly',
                'billing_cycle' => 'monthly',
                'bags_per_month' => 8,
                'price' => 259.99,
                'bag_overage_rate' => 32.50,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],

            // Yearly plans (15% discount)
            [
                'name' => '1 Bag Yearly',
                'billing_cycle' => 'yearly',
                'bags_per_month' => 1,
                'price' => 407.89, // $39.99 * 12 * 0.85
                'bag_overage_rate' => 39.99,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
            [
                'name' => '2 Bags Yearly',
                'billing_cycle' => 'yearly',
                'bags_per_month' => 2,
                'price' => 764.89, // $74.99 * 12 * 0.85
                'bag_overage_rate' => 37.50,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
            [
                'name' => '4 Bags Yearly',
                'billing_cycle' => 'yearly',
                'bags_per_month' => 4,
                'price' => 1427.89, // $139.99 * 12 * 0.85
                'bag_overage_rate' => 35.00,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
            [
                'name' => '8 Bags Yearly',
                'billing_cycle' => 'yearly',
                'bags_per_month' => 8,
                'price' => 2651.89, // $259.99 * 12 * 0.85
                'bag_overage_rate' => 32.50,
                'price_per_lb_overage' => 2.99,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('lce_subscription_plans')->insert(array_merge($plan, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
