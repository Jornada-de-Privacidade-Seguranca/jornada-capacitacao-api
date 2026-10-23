<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Repositories\Eloquent\CriterionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriterionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_a_criterion_weight_by_either_language_name(): void
    {
        Criterion::create([
            'NO_CRITERIO_PT' => 'Critério Personalizado',
            'NO_CRITERIO_EN' => 'Custom Criterion',
            'NU_PESO_CRITERIO' => 12.5,
        ]);

        $repository = new CriterionRepository;

        $this->assertSame(12.5, $repository->findWeightByName('Custom Criterion'));
        $this->assertSame(12.5, $repository->findWeightByName('Critério Personalizado'));
    }

    public function test_it_returns_null_when_the_criterion_or_its_weight_is_missing(): void
    {
        $repository = new CriterionRepository;

        $this->assertNull($repository->findWeightByName('Missing Criterion'));

        Criterion::create([
            'NO_CRITERIO_PT' => 'Critério sem peso',
            'NO_CRITERIO_EN' => 'Criterion without weight',
            'NU_PESO_CRITERIO' => null,
        ]);

        $this->assertNull($repository->findWeightByName('Criterion without weight'));
    }
}
