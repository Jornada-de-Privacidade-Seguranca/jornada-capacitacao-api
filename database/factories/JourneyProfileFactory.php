<?php

namespace Database\Factories;

use App\Models\JourneyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class JourneyProfileFactory extends Factory
{
    protected $model = JourneyProfile::class;

    public function definition(): array
    {
        return [
            'NO_PERFIS_PT' => fake()->words(3, true),
            'NO_PERFIS_EN' => fake()->words(3, true),
            'DS_PERFIS_PT' => fake()->sentence(3),
            'DS_PERFIS_EN' => fake()->sentence(3),
        ];
    }
}
