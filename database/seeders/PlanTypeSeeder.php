<?php

namespace Database\Seeders;

use App\Models\PlanType;
use Illuminate\Database\Seeder;

class PlanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Monthly',
                'duration_month' => 1,
                'price' => 100,
                'created_at' => now()
            ],
            [
                'name' => '6 Months',
                'duration_month' => 6,
                'price' => 600,
                'created_at' => now()
            ],
            [
                'name' => '12 Months',
                'duration_month' => 12,
                'price' => 1200,
                'created_at' => now()
            ]
        ];

        PlanType::insert($types);
    }
}
