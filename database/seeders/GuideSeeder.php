<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific guides with known data
        \App\Models\Guide::create([
            'name' => 'John Smith',
            'experience_years' => 15,
            'is_active' => true,
        ]);

        \App\Models\Guide::create([
            'name' => 'Maria Garcia',
            'experience_years' => 10,
            'is_active' => true,
        ]);

        \App\Models\Guide::create([
            'name' => 'Robert Johnson',
            'experience_years' => 5,
            'is_active' => true,
        ]);

        \App\Models\Guide::create([
            'name' => 'Anna Williams',
            'experience_years' => 3,
            'is_active' => true,
        ]);

        \App\Models\Guide::create([
            'name' => 'Retired Guide',
            'experience_years' => 20,
            'is_active' => false,
        ]);
    }
}
