<?php

namespace App\Repositories\Contracts;

use App\Models\CompetencyCriterionWeight;
use Illuminate\Database\Eloquent\Collection;

interface CompetencyCriterionWeightRepositoryInterface
{
    public function all(): Collection;

    public function findByJourneyProfileId(int $journeyProfileId): Collection;

    public function findOrFail(int $id): CompetencyCriterionWeight;
}
