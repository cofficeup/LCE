<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders
        $this->call([
            SubscriptionPlanSeeder::class,
            HolidaySeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
