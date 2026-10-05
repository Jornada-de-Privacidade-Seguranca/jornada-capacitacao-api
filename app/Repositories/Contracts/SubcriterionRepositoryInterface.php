<?php

namespace App\Repositories\Contracts;

use App\Models\Subcriterion;
use Illuminate\Database\Eloquent\Collection;

interface SubcriterionRepositoryInterface
{
    public function all(): Collection;

    public function allByCriterionId(int $criterionId): Collection;

    public function findOrFail(int $id): Subcriterion;
}
