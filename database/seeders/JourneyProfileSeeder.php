<?php

namespace Database\Seeders;

use App\Models\JourneyProfile;
use Illuminate\Database\Seeder;

class JourneyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JourneyProfile::factory()->count(30)->create();
    }
}
