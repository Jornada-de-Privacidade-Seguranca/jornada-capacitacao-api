<?php

namespace App\Repositories\Eloquent;

use App\Models\CompetencyCriterionWeight;
use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompetencyCriterionWeightRepository implements CompetencyCriterionWeightRepositoryInterface
{
    public function all(): Collection
    {
        return CompetencyCriterionWeight::query()
            ->with(['journeyProfile', 'knowledgeArea'])
            ->orderBy('PK_PESO_CRIT_COMPETENCIA')
            ->get();
    }

    public function findByJourneyProfileId(int $journeyProfileId): Collection
    {
        return CompetencyCriterionWeight::query()
            ->where('FK_PERFIS_JORNADA', $journeyProfileId)
            ->orderBy('PK_PESO_CRIT_COMPETENCIA')
            ->get();
    }

    public function findOrFail(int $id): CompetencyCriterionWeight
    {
        return CompetencyCriterionWeight::query()
            ->with(['journeyProfile', 'knowledgeArea'])
            ->findOrFail($id);
    }
}
