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
use Illuminate\Support\Collection;

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
            ->whereNull('DH_CANCELADA_EM')
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
            ->whereNull('DH_CANCELADA_EM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA');
    }

    public function findLatestByUserForUpdate(int $userId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_CANCELADA_EM')
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

        $this->applyStatusFilter($query, $filters['status'] ?? null);

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

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        match ($status) {
            'completed' => $query->whereNotNull('DH_FIM')->whereNull('DH_CANCELADA_EM'),
            'ongoing' => $query->whereNull('DH_FIM')->whereNull('DH_CANCELADA_EM'),
            'cancelled' => $query->whereNotNull('DH_CANCELADA_EM'),
            default => null,
        };
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
            ->whereNull('DH_CANCELADA_EM')
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

        $summary = $this->fetchOverallSummaryForUser($userId);
        $journeyStatsByDomain = $this->fetchJourneyStatsByDomain($userId);
        $latestScoresByDomain = $this->fetchLatestScoresByDomain($userId);
        $competencyStatsByDomain = $this->fetchCompetencyStatsByDomain($userId);

        $domainMetrics = [];

        foreach ($domains as $domain) {
            $domainId = (int) $domain->PK_DOMINIO_CONHECIMENTO;
            $domainMetrics[] = $this->buildDomainMetricEntry(
                domain: $domain,
                journeyStat: $journeyStatsByDomain->get($domainId),
                latestScore: $latestScoresByDomain->get($domainId),
                competencyStat: $competencyStatsByDomain->get($domainId),
            );
        }

        return [
            'user_id' => $userId,
            'summary' => $summary,
            'domains' => $domainMetrics,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOverallSummaryForUser(int $userId): array
    {
        $connection = Journey::query()->getConnection();
        $grammar = $connection->getQueryGrammar();

        $summaryRow = $connection->table('TB_JORNADA')
            ->where('FK_USUARIO', $userId)
            ->selectRaw('COUNT(*) as total_journeys')
            ->selectRaw("SUM(CASE WHEN {$grammar->wrap('DH_FIM')} IS NOT NULL AND {$grammar->wrap('DH_CANCELADA_EM')} IS NULL THEN 1 ELSE 0 END) as completed_journeys")
            ->selectRaw("SUM(CASE WHEN {$grammar->wrap('DH_FIM')} IS NULL AND {$grammar->wrap('DH_CANCELADA_EM')} IS NULL THEN 1 ELSE 0 END) as ongoing_journeys")
            ->selectRaw("AVG(CASE WHEN {$grammar->wrap('DH_FIM')} IS NOT NULL AND {$grammar->wrap('DH_CANCELADA_EM')} IS NULL THEN {$grammar->wrap('NU_PONTUACAO_TOTAL')} END) as average_score")
            ->selectRaw("MAX(CASE WHEN {$grammar->wrap('DH_FIM')} IS NOT NULL AND {$grammar->wrap('DH_CANCELADA_EM')} IS NULL THEN {$grammar->wrap('NU_PONTUACAO_TOTAL')} END) as highest_score")
            ->first();

        $latestScore = Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNotNull('DH_FIM')
            ->whereNull('DH_CANCELADA_EM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->limit(1)
            ->value('NU_PONTUACAO_TOTAL');

        $summaryData = $summaryRow !== null ? (array) $summaryRow : [];

        return [
            'total_journeys' => (int) ($summaryData['total_journeys'] ?? 0),
            'completed_journeys' => (int) ($summaryData['completed_journeys'] ?? 0),
            'ongoing_journeys' => (int) ($summaryData['ongoing_journeys'] ?? 0),
            'average_score' => isset($summaryData['average_score']) ? round((float) $summaryData['average_score'], 2) : null,
            'highest_score' => isset($summaryData['highest_score']) ? round((float) $summaryData['highest_score'], 2) : null,
            'latest_score' => $latestScore !== null ? (float) $latestScore : null,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchJourneyStatsByDomain(int $userId): Collection
    {
        $connection = Journey::query()->getConnection();
        $grammar = $connection->getQueryGrammar();

        return $connection->table('TB_JORNADA')
            ->join('TB_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', '=', 'TB_JORNADA.FK_PERFIS_JORNADA')
            ->where('TB_JORNADA.FK_USUARIO', $userId)
            ->whereNotNull('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->groupBy('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->select('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO as domain_id')
            ->selectRaw('COUNT(*) as total_journeys')
            ->selectRaw("SUM(CASE WHEN {$grammar->wrap('TB_JORNADA.DH_FIM')} IS NOT NULL AND {$grammar->wrap('TB_JORNADA.DH_CANCELADA_EM')} IS NULL THEN 1 ELSE 0 END) as completed_journeys")
            ->selectRaw("SUM(CASE WHEN {$grammar->wrap('TB_JORNADA.DH_FIM')} IS NULL AND {$grammar->wrap('TB_JORNADA.DH_CANCELADA_EM')} IS NULL THEN 1 ELSE 0 END) as ongoing_journeys")
            ->selectRaw("AVG(CASE WHEN {$grammar->wrap('TB_JORNADA.DH_FIM')} IS NOT NULL AND {$grammar->wrap('TB_JORNADA.DH_CANCELADA_EM')} IS NULL THEN {$grammar->wrap('TB_JORNADA.NU_PONTUACAO_TOTAL')} END) as average_score")
            ->selectRaw("MAX(CASE WHEN {$grammar->wrap('TB_JORNADA.DH_FIM')} IS NOT NULL AND {$grammar->wrap('TB_JORNADA.DH_CANCELADA_EM')} IS NULL THEN {$grammar->wrap('TB_JORNADA.NU_PONTUACAO_TOTAL')} END) as highest_score")
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) ((array) $row)['domain_id'] => (array) $row]);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function fetchLatestScoresByDomain(int $userId): Collection
    {
        $connection = Journey::query()->getConnection();
        $grammar = $connection->getQueryGrammar();

        $rankedJourneys = $connection->table('TB_JORNADA')
            ->join('TB_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', '=', 'TB_JORNADA.FK_PERFIS_JORNADA')
            ->where('TB_JORNADA.FK_USUARIO', $userId)
            ->whereNotNull('TB_JORNADA.DH_FIM')
            ->whereNull('TB_JORNADA.DH_CANCELADA_EM')
            ->whereNotNull('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->select([
                'TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO as domain_id',
                'TB_JORNADA.NU_PONTUACAO_TOTAL as score',
            ])
            ->selectRaw(
                "ROW_NUMBER() OVER (PARTITION BY {$grammar->wrap('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')} ORDER BY {$grammar->wrap('TB_JORNADA.DH_CRIADO_EM')} DESC, {$grammar->wrap('TB_JORNADA.PK_JORNADA')} DESC) as row_num"
            );

        return $connection->query()
            ->fromSub($rankedJourneys, 'ranked_domain_journeys')
            ->where('row_num', 1)
            ->get()
            ->mapWithKeys(function (object $row): array {
                $rowData = (array) $row;

                return [(int) $rowData['domain_id'] => $rowData['score']];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchCompetencyStatsByDomain(int $userId): Collection
    {
        $connection = Journey::query()->getConnection();
        $grammar = $connection->getQueryGrammar();

        return $connection->table('TB_CONHECIMENTOS_ADQUIRIDOS')
            ->join('TB_JORNADA', 'TB_JORNADA.PK_JORNADA', '=', 'TB_CONHECIMENTOS_ADQUIRIDOS.FK_JORNADA')
            ->join('TB_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', '=', 'TB_JORNADA.FK_PERFIS_JORNADA')
            ->where('TB_JORNADA.FK_USUARIO', $userId)
            ->whereNull('TB_JORNADA.DH_CANCELADA_EM')
            ->whereNotNull('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->whereNotNull('TB_CONHECIMENTOS_ADQUIRIDOS.FK_COMPETENCIAS')
            ->groupBy('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->select('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO as domain_id')
            ->selectRaw("COUNT(DISTINCT {$grammar->wrap('TB_CONHECIMENTOS_ADQUIRIDOS.FK_COMPETENCIAS')}) as total_competencies_evaluated")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$grammar->wrap('TB_CONHECIMENTOS_ADQUIRIDOS.TP_ADQUIRIDO')} = 1 THEN {$grammar->wrap('TB_CONHECIMENTOS_ADQUIRIDOS.FK_COMPETENCIAS')} END) as acquired_competencies_count")
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) ((array) $row)['domain_id'] => (array) $row]);
    }

    /**
     * @param  array<string, mixed>|null  $journeyStat
     * @param  array<string, mixed>|null  $competencyStat
     * @return array<string, mixed>
     */
    private function buildDomainMetricEntry(
        KnowledgeDomain $domain,
        ?array $journeyStat,
        mixed $latestScore,
        ?array $competencyStat,
    ): array {
        $evaluatedCompetencies = (int) ($competencyStat['total_competencies_evaluated'] ?? 0);
        $acquiredCompetencies = (int) ($competencyStat['acquired_competencies_count'] ?? 0);
        $acquisitionRate = $evaluatedCompetencies > 0
            ? round(($acquiredCompetencies / $evaluatedCompetencies) * 100, 2)
            : null;

        return [
            'domain_id' => (int) $domain->PK_DOMINIO_CONHECIMENTO,
            'domain_name_pt' => $domain->NO_DOMINIO_CONHECIMENTO_PT,
            'domain_name_en' => $domain->NO_DOMINIO_CONHECIMENTO_EN,
            'total_journeys' => (int) ($journeyStat['total_journeys'] ?? 0),
            'completed_journeys' => (int) ($journeyStat['completed_journeys'] ?? 0),
            'ongoing_journeys' => (int) ($journeyStat['ongoing_journeys'] ?? 0),
            'average_score' => isset($journeyStat['average_score']) ? round((float) $journeyStat['average_score'], 2) : null,
            'highest_score' => isset($journeyStat['highest_score']) ? round((float) $journeyStat['highest_score'], 2) : null,
            'latest_score' => $latestScore !== null ? (float) $latestScore : null,
            'acquired_competencies_count' => $acquiredCompetencies,
            'total_competencies_evaluated' => $evaluatedCompetencies,
            'competency_acquisition_rate' => $acquisitionRate,
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
