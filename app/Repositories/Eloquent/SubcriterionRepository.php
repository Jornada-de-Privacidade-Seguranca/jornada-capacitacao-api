<?php

namespace App\Repositories\Eloquent;

use App\Models\Subcriterion;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubcriterionRepository implements SubcriterionRepositoryInterface
{
    public function all(): Collection
    {
        return Subcriterion::query()
            ->with('criterion')
            ->orderBy('PK_SUBCRITERIOS')
            ->get();
    }

    public function allByCriterionNamePt(string $criterionNamePt): Collection
    {
        return Subcriterion::query()
            ->whereHas('criterion', function ($query) use ($criterionNamePt): void {
                $query->where('NO_CRITERIO_PT', $criterionNamePt);
            })
            ->orderBy('PK_SUBCRITERIOS')
            ->get();
    }

    public function findOrFail(int $id): Subcriterion
    {
        return Subcriterion::query()
            ->with('criterion')
            ->findOrFail($id);
    }
}
