<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LCE Business Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for Laundry Care Express business logic
    |
    */

    // Welcome Credit
    'welcome_credit' => env('LCE_WELCOME_CREDIT', 20.00),

    // PPO Pricing
    'ppo_rate_per_lb' => env('LCE_PPO_RATE_PER_LB', 2.99),
    'ppo_minimum' => env('LCE_PPO_MINIMUM', 30.00),
    'pickup_fee' => env('LCE_PICKUP_FEE', 9.99),
    'service_fee' => env('LCE_SERVICE_FEE', 5.00),

    // Subscription Configuration
    'bag_weight_lbs' => env('LCE_BAG_WEIGHT_LBS', 20.5),
    'annual_discount_percent' => env('LCE_ANNUAL_DISCOUNT_PERCENT', 15),

    // Scheduling
    'cutoff_time' => env('LCE_CUTOFF_TIME', '14:00'),

    // Refund Rules
    'refund_grace_days' => env('LCE_REFUND_GRACE_DAYS', 5),
    'refund_penalty_annual' => env('LCE_REFUND_PENALTY_ANNUAL', 100.00),
];
