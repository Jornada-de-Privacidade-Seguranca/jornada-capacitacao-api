<?php

namespace Database\Factories;

use App\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetencyFactory extends Factory
{
    protected $model = Competency::class;

    public function definition(): array
    {
        return [
            'NO_COMPETENCIAS_PT' => fake()->words(3, true),
            'DS_COMPETENCIAS_PT' => fake()->sentence(),
        ];
    }
}
