<?php

namespace App\Repositories\Eloquent;

use App\Models\Criterion;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CriterionRepository implements CriterionRepositoryInterface
{
    public function all(): Collection
    {
        return Criterion::query()
            ->orderBy('PK_CRITERIOS')
            ->get();
    }

    public function findOrFail(int $id): Criterion
    {
        return Criterion::query()->findOrFail($id);
    }
}
