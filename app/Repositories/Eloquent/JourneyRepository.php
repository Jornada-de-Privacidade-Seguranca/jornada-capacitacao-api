<?php

namespace App\Repositories\Eloquent;

use App\Models\AcquiredKnowledge;
use App\Models\Journey;
use App\Models\KnowledgeDomain;
use App\Models\ProfileCompetency;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Eloquent\Concerns\AppliesNormalizedSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class JourneyRepository implements JourneyRepositoryInterface
{
    use AppliesNormalizedSearch;

    private const DETAILED_RELATIONS = [
        'usuario.profileType',
        'journeyProfile.knowledgeDomain',
        'journeyProfile.competencies.knowledgeDomain',
        'journeyProfile.competencies.knowledgeArea',
        'journeyProfile.competencies.difficulty',
        'professionalExperience',
        'profileExperience',
        'englishSubcriterion.criterion',
        'higherEducationCourses.subcriterion.criterion',
        'acquiredKnowledges.competency.knowledgeDomain',
        'acquiredKnowledges.competency.knowledgeArea',
        'acquiredKnowledges.competency.difficulty',
        'professionalCertifications.knowledgeDomain',
    ];

    public function findCurrentByUserId(int $userId): ?Journey
    {
        return Journey::query()
            ->with([
                'professionalExperience',
                'profileExperience',
                'englishSubcriterion',
            ])
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->first();
    }

    public function findDetailedById(int $journeyId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('PK_JORNADA', $journeyId)
            ->first();
    }

    public function findDetailedByIdForUpdate(int $journeyId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('PK_JORNADA', $journeyId)
            ->lockForUpdate()
            ->first();
    }

    public function findOngoingByUser(int $userId): ?Journey
    {
        return $this->ongoingByUserQuery($userId)->first();
    }

    public function findOngoingByUserForUpdate(int $userId): ?Journey
    {
        return $this->ongoingByUserQuery($userId)
            ->lockForUpdate()
            ->first();
    }

    private function ongoingByUserQuery(int $userId): Builder
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA');
    }

    public function findLatestByUserForUpdate(int $userId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('FK_USUARIO', $userId)
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->lockForUpdate()
            ->first();
    }

    public function syncProfessionalCertifications(Journey $journey, array $certificationIds): Journey
    {
        $journey->professionalCertifications()->sync($certificationIds);

        return $journey->load([
            'professionalCertifications' => fn ($query) => $query->orderBy(
                'TB_CERTIFICACAO_PROFISSIONAL.PK_CERTIFICACAO_PROFISSIONAL'
            ),
            'professionalCertifications.knowledgeDomain',
        ]);
    }

    public function paginatedByUser(int $userId, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Journey::query()
            ->with([
                'usuario',
                'journeyProfile.knowledgeDomain',
            ])
            ->withCount([
                'acquiredKnowledges',
                'professionalCertifications',
            ])
            ->where('FK_USUARIO', $userId);

        if (($filters['status'] ?? null) === 'completed') {
            $query->whereNotNull('DH_FIM');
        }

        if (($filters['status'] ?? null) === 'ongoing') {
            $query->whereNull('DH_FIM');
        }

        if (isset($filters['journey_profile_id'])) {
            $query->where('FK_PERFIS_JORNADA', $filters['journey_profile_id']);
        }

        $this->applySearchFilters($query, $filters);

        if (isset($filters['started_from'])) {
            $query->whereDate('DH_CRIADO_EM', '>=', $filters['started_from']);
        }

        if (isset($filters['started_to'])) {
            $query->whereDate('DH_CRIADO_EM', '<=', $filters['started_to']);
        }

        if (isset($filters['ended_from'])) {
            $query->whereDate('DH_FIM', '>=', $filters['ended_from']);
        }

        if (isset($filters['ended_to'])) {
            $query->whereDate('DH_FIM', '<=', $filters['ended_to']);
        }

        return $query
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function updateGeneralData(Journey $journey, array $data): Journey
    {
        $columns = [
            'english_level_id' => 'FK_SUBCRITERIOS_INGLES',
            'professional_experience_id' => 'FK_EXPERIENCIA_PROFISSIONAL',
            'profile_experience_id' => 'FK_ATUACAO_PERFIL',
        ];

        $attributes = [];

        foreach ($columns as $field => $column) {
            if (array_key_exists($field, $data)) {
                $attributes[$column] = $data[$field];
            }
        }

        $journey->fill($attributes);
        $journey->save();

        return $journey->load([
            'professionalExperience',
            'profileExperience',
            'englishSubcriterion',
        ]);
    }

    public function hasOngoingJourney(int $userId): bool
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->exists();
    }

    public function hasAnyJourney(int $userId): bool
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->exists();
    }

    public function hasJourneyForProfile(int $userId, int $journeyProfileId): bool
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->where('FK_PERFIS_JORNADA', $journeyProfileId)
            ->exists();
    }

    public function create(array $data): Journey
    {
        $journey = Journey::query()->create($data);

        return $journey->load(['usuario', 'journeyProfile.knowledgeDomain']);
    }

    public function update(Journey $journey, array $data): void
    {
        $journey->update($data);
    }

    public function attachCourses(Journey $journey, array $courseIds): void
    {
        $journey->higherEducationCourses()->syncWithoutDetaching($courseIds);
    }

    public function syncCourses(Journey $journey, array $courseIds): void
    {
        $journey->higherEducationCourses()->sync($courseIds);
    }

    public function attachCertifications(Journey $journey, array $certificationIds): void
    {
        $journey->professionalCertifications()->syncWithoutDetaching($certificationIds);
    }

    public function upsertAcquiredKnowledges(Journey $journey, array $competencies): void
    {
        foreach ($competencies as $competency) {
            AcquiredKnowledge::query()->updateOrCreate(
                [
                    'FK_JORNADA' => $journey->PK_JORNADA,
                    'FK_COMPETENCIAS' => $competency['competency_id'],
                ],
                [
                    'TP_ADQUIRIDO' => $competency['is_acquired'] ? 1 : 0,
                ],
            );
        }
    }

    public function competencyIdsBelongToProfile(int $journeyProfileId, array $competencyIds): bool
    {
        $distinctCompetencyIds = array_unique($competencyIds);

        $matchingCount = ProfileCompetency::query()
            ->where('FK_PERFIS_JORNADA', $journeyProfileId)
            ->whereIn('FK_COMPETENCIAS', $distinctCompetencyIds)
            ->distinct()
            ->count('FK_COMPETENCIAS');

        return $matchingCount === count($distinctCompetencyIds);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomainMetricsForUser(int $userId): array
    {
        $domains = KnowledgeDomain::query()
            ->orderBy('PK_DOMINIO_CONHECIMENTO')
            ->get();

        $journeys = Journey::query()
            ->with([
                'journeyProfile.knowledgeDomain',
                'acquiredKnowledges',
            ])
            ->where('FK_USUARIO', $userId)
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->get();

        $completedJourneys = $journeys->whereNotNull('DH_FIM');
        $completedScores = $completedJourneys
            ->pluck('NU_PONTUACAO_TOTAL')
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score);

        $summary = [
            'total_journeys' => $journeys->count(),
            'completed_journeys' => $completedJourneys->count(),
            'ongoing_journeys' => $journeys->whereNull('DH_FIM')->count(),
            'average_score' => $completedScores->isNotEmpty() ? round($completedScores->avg(), 2) : null,
            'highest_score' => $completedScores->isNotEmpty() ? round($completedScores->max(), 2) : null,
            'latest_score' => $completedJourneys->first()?->NU_PONTUACAO_TOTAL !== null
                ? (float) $completedJourneys->first()->NU_PONTUACAO_TOTAL
                : null,
        ];

        $domainMetrics = [];

        foreach ($domains as $domain) {
            $domainId = (int) $domain->PK_DOMINIO_CONHECIMENTO;
            $domainJourneys = $journeys->filter(function (Journey $journey) use ($domainId): bool {
                return (int) ($journey->journeyProfile?->FK_DOMINIO_CONHECIMENTO ?? 0) === $domainId;
            });

            $domainCompleted = $domainJourneys->whereNotNull('DH_FIM');
            $domainScores = $domainCompleted
                ->pluck('NU_PONTUACAO_TOTAL')
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score);

            $allDomainAcquiredKnowledges = $domainJourneys->flatMap(
                fn (Journey $journey) => $journey->acquiredKnowledges
            );

            $evaluatedCompetencies = $allDomainAcquiredKnowledges
                ->pluck('FK_COMPETENCIAS')
                ->filter()
                ->unique()
                ->count();

            $acquiredCompetencies = $allDomainAcquiredKnowledges
                ->filter(fn ($knowledge) => (int) $knowledge->TP_ADQUIRIDO === 1)
                ->pluck('FK_COMPETENCIAS')
                ->filter()
                ->unique()
                ->count();

            $acquisitionRate = $evaluatedCompetencies > 0
                ? round(($acquiredCompetencies / $evaluatedCompetencies) * 100, 2)
                : null;

            $domainMetrics[] = [
                'domain_id' => $domainId,
                'domain_name_pt' => $domain->NO_DOMINIO_CONHECIMENTO_PT,
                'domain_name_en' => $domain->NO_DOMINIO_CONHECIMENTO_EN,
                'total_journeys' => $domainJourneys->count(),
                'completed_journeys' => $domainCompleted->count(),
                'ongoing_journeys' => $domainJourneys->whereNull('DH_FIM')->count(),
                'average_score' => $domainScores->isNotEmpty() ? round($domainScores->avg(), 2) : null,
                'highest_score' => $domainScores->isNotEmpty() ? round($domainScores->max(), 2) : null,
                'latest_score' => $domainCompleted->first()?->NU_PONTUACAO_TOTAL !== null
                    ? (float) $domainCompleted->first()->NU_PONTUACAO_TOTAL
                    : null,
                'acquired_competencies_count' => $acquiredCompetencies,
                'total_competencies_evaluated' => $evaluatedCompetencies,
                'competency_acquisition_rate' => $acquisitionRate,
            ];
        }

        return [
            'user_id' => $userId,
            'summary' => $summary,
            'domains' => $domainMetrics,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySearchFilters(Builder $query, array $filters): void
    {
        if (isset($filters['knowledge_domain_id'])) {
            $domainId = (int) $filters['knowledge_domain_id'];
            $query->whereHas('journeyProfile', function (Builder $profileQuery) use ($domainId): void {
                $profileQuery->where('FK_DOMINIO_CONHECIMENTO', $domainId);
            });
        }

        if (isset($filters['domains']) && is_array($filters['domains']) && ! empty($filters['domains'])) {
            $domainIds = array_map('intval', $filters['domains']);
            $query->whereHas('journeyProfile', function (Builder $profileQuery) use ($domainIds): void {
                $profileQuery->whereIn('FK_DOMINIO_CONHECIMENTO', $domainIds);
            });
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $this->applyJourneySearch($query, $search);
        }
    }

    private function applyJourneySearch(Builder $query, string $search): void
    {
        $isNumeric = is_numeric($search);
        $escapedSearchTerm = $this->escapeSearchWildcards($this->normalizeSearchTerm($search));
        $pattern = '%'.$escapedSearchTerm.'%';
        $columns = ['NO_PERFIS_PT', 'NO_PERFIS_EN', 'DS_PERFIS_PT', 'DS_PERFIS_EN'];

        $query->where(function (Builder $sub) use ($isNumeric, $search, $columns, $pattern): void {
            if ($isNumeric) {
                $sub->orWhere('TB_JORNADA.PK_JORNADA', (int) $search);
            }

            $sub->orWhereHas('journeyProfile', function (Builder $profileQuery) use ($columns, $pattern): void {
                $profileQuery->where(function (Builder $profileSubQuery) use ($columns, $pattern): void {
                    $connection = $profileSubQuery->getConnection();
                    foreach ($columns as $column) {
                        $wrappedColumn = $profileSubQuery->getQuery()->getGrammar()->wrap($column);
                        $normalizedColumn = $this->normalizedSearchExpression($connection, $wrappedColumn);
                        $profileSubQuery->orWhereRaw("{$normalizedColumn} LIKE ? ESCAPE '\\'", [$pattern]);
                    }
                });
            });
        });
    }
}
