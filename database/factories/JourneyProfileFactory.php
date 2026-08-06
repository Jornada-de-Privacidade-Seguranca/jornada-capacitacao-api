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
            'DS_PERFIS_PT' => fake()->sentence(3),
            'DS_PERFIS_EN' => fake()->sentence(3),
        ];
    }
}
