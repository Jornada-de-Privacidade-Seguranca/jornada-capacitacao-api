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

    public function findEnglishCriterionId(): ?int
    {
        return Criterion::query()
            ->where('NO_CRITERIO_EN', Criterion::ENGLISH_PROFICIENCY_NAME)
            ->value('PK_CRITERIOS');
    }

    public function findWeightByName(string $name): ?float
    {
        $weight = Criterion::query()
            ->where('NO_CRITERIO_PT', $name)
            ->orWhere('NO_CRITERIO_EN', $name)
            ->value('NU_PESO_CRITERIO');

        return $weight === null ? null : (float) $weight;
    }
}
