<?php

namespace App\Repositories\Eloquent;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Repositories\Contracts\CurrentJourneyCompetencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CurrentJourneyCompetencyRepository implements CurrentJourneyCompetencyRepositoryInterface
{
    public function optionsForProfile(int $journeyProfileId): Collection
    {
        return Competency::query()
            ->with(['knowledgeDomain', 'knowledgeArea', 'difficulty'])
            ->whereHas('journeyProfiles', function ($query) use ($journeyProfileId): void {
                $query->where('TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', $journeyProfileId);
            })
            ->orderBy('PK_COMPETENCIAS')
            ->get();
    }

    public function acquiredForJourney(int $journeyId, int $journeyProfileId): Collection
    {
        return AcquiredKnowledge::query()
            ->with(['competency.knowledgeDomain', 'competency.knowledgeArea', 'competency.difficulty'])
            ->where('FK_JORNADA', $journeyId)
            ->whereHas('competency.journeyProfiles', function ($query) use ($journeyProfileId): void {
                $query->where('TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', $journeyProfileId);
            })
            ->join('TB_COMPETENCIAS', 'TB_COMPETENCIAS.PK_COMPETENCIAS', '=', 'TB_CONHECIMENTOS_ADQUIRIDOS.FK_COMPETENCIAS')
            ->orderBy('TB_COMPETENCIAS.PK_COMPETENCIAS')
            ->select('TB_CONHECIMENTOS_ADQUIRIDOS.*')
            ->get();
    }

    public function countOptionsForProfile(int $journeyProfileId): int
    {
        return Competency::query()
            ->whereHas('journeyProfiles', function ($query) use ($journeyProfileId): void {
                $query->where('TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', $journeyProfileId);
            })
            ->count();
    }

    public function countMatchingOptionsForProfile(int $journeyProfileId, array $competencyIds): int
    {
        if ($competencyIds === []) {
            return 0;
        }

        return Competency::query()
            ->whereIn('PK_COMPETENCIAS', $competencyIds)
            ->whereHas('journeyProfiles', function ($query) use ($journeyProfileId): void {
                $query->where('TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', $journeyProfileId);
            })
            ->count();
    }

    public function replaceForJourney(int $journeyId, array $competencies): void
    {
        DB::transaction(function () use ($journeyId, $competencies): void {
            AcquiredKnowledge::query()
                ->where('FK_JORNADA', $journeyId)
                ->delete();

            foreach ($competencies as $competency) {
                AcquiredKnowledge::query()->create([
                    'FK_JORNADA' => $journeyId,
                    'FK_COMPETENCIAS' => $competency['competency_id'],
                    'TP_ADQUIRIDO' => $competency['acquired'] ? 1 : 0,
                ]);
            }
        });
    }
}
