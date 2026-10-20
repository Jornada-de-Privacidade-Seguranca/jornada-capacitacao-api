<?php

namespace App\Repositories\Eloquent;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Repositories\Contracts\CurrentJourneyCompetencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CurrentJourneyCompetencyRepository implements CurrentJourneyCompetencyRepositoryInterface
{
    public function optionsForProfile(int $journeyProfileId, ?string $modality = null): Collection
    {
        $query = Competency::query()
            ->with(['knowledgeDomain', 'knowledgeArea', 'difficulty'])
            ->whereHas('journeyProfiles', function ($query) use ($journeyProfileId): void {
                $query->where('TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', $journeyProfileId);
            });

        $domainNames = match ($modality) {
            'Privacidade' => ['Privacidade', 'Privacy', 'Conjunta (Privacidade/Segurança)', 'Joint (Privacy/security)'],
            'Segurança' => ['Segurança da Informação', 'Information Security', 'Conjunta (Privacidade/Segurança)', 'Joint (Privacy/security)'],
            default => null,
        };

        if ($domainNames !== null) {
            $query->whereHas('knowledgeDomain', function ($query) use ($domainNames): void {
                $query->whereIn('NO_DOMINIO_CONHECIMENTO_PT', $domainNames)
                    ->orWhereIn('NO_DOMINIO_CONHECIMENTO_EN', $domainNames);
            });
        }

        return $query->orderBy('PK_COMPETENCIAS')->get();
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
        AcquiredKnowledge::query()
            ->where('FK_JORNADA', $journeyId)
            ->delete();

        if ($competencies === []) {
            return;
        }

        AcquiredKnowledge::query()->insert(array_map(
            fn (array $competency): array => [
                'FK_JORNADA' => $journeyId,
                'FK_COMPETENCIAS' => $competency['competency_id'],
                'TP_ADQUIRIDO' => $competency['acquired'] ? 1 : 0,
            ],
            $competencies,
        ));
    }
}
