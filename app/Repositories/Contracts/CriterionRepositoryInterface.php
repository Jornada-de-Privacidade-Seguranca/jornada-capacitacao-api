<?php

namespace App\Repositories\Contracts;

use App\Models\Criterion;
use Illuminate\Database\Eloquent\Collection;

interface CriterionRepositoryInterface
{
    public function all(): Collection;

    public function findOrFail(int $id): Criterion;

    public function findEnglishCriterionId(): ?int;
}
