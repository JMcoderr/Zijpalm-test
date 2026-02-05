<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 3 yearly reports
        Report::factory()->count(3)->generateYearly()->create();

        // Report related to activites are generated from within the Activity Seeder
    }
}
