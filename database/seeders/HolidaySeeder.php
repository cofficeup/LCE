<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            // 2025 US Holidays
            ['name' => 'New Year\'s Day', 'date' => '2025-01-01'],
            ['name' => 'Martin Luther King Jr. Day', 'date' => '2025-01-20'],
            ['name' => 'Presidents\' Day', 'date' => '2025-02-17'],
            ['name' => 'Memorial Day', 'date' => '2025-05-26'],
            ['name' => 'Independence Day', 'date' => '2025-07-04'],
            ['name' => 'Labor Day', 'date' => '2025-09-01'],
            ['name' => 'Thanksgiving Day', 'date' => '2025-11-27'],
            ['name' => 'Christmas Day', 'date' => '2025-12-25'],

            // 2026 US Holidays
            ['name' => 'New Year\'s Day', 'date' => '2026-01-01'],
            ['name' => 'Martin Luther King Jr. Day', 'date' => '2026-01-19'],
            ['name' => 'Presidents\' Day', 'date' => '2026-02-16'],
            ['name' => 'Memorial Day', 'date' => '2026-05-25'],
            ['name' => 'Independence Day', 'date' => '2026-07-04'],
            ['name' => 'Labor Day', 'date' => '2026-09-07'],
            ['name' => 'Thanksgiving Day', 'date' => '2026-11-26'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25'],
        ];

        foreach ($holidays as $holiday) {
            DB::table('lce_holidays')->insert(array_merge($holiday, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
