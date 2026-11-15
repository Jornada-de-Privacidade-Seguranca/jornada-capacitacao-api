<?php

namespace App\Repositories\Eloquent;

use App\Models\Journey;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use App\Repositories\Contracts\AdminResultRepositoryInterface;
use App\Repositories\Eloquent\Concerns\AppliesNormalizedSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class AdminResultRepository implements AdminResultRepositoryInterface
{
    use AppliesNormalizedSearch;

    public function paginatedCompletedResults(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Journey::query()
            ->with([
                'usuario',
                'journeyProfile.knowledgeDomain',
            ])
            ->join('TB_USUARIO', 'TB_USUARIO.PK_USUARIO', '=', 'TB_JORNADA.FK_USUARIO')
            ->join('TB_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', '=', 'TB_JORNADA.FK_PERFIS_JORNADA')
            ->select('TB_JORNADA.*');

        $this->applyCompletedResultFilters($query->getQuery(), $filters);

        return $query
            ->orderByDesc('TB_JORNADA.DH_FIM')
            ->orderByDesc('TB_JORNADA.PK_JORNADA')
            ->paginate($perPage, ['TB_JORNADA.*'], 'page', $page);
    }

    public function getCompletedResultsMetrics(array $filters): array
    {
        $connection = Journey::query()->getConnection();
        $grammar = $connection->getQueryGrammar();

        $baseQuery = $connection->table('TB_JORNADA')
            ->join('TB_USUARIO', 'TB_USUARIO.PK_USUARIO', '=', 'TB_JORNADA.FK_USUARIO')
            ->join('TB_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA', '=', 'TB_JORNADA.FK_PERFIS_JORNADA');

        $this->applyCompletedResultFilters($baseQuery, $filters);

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_completed_journeys')
            ->selectRaw("COUNT(DISTINCT {$grammar->wrap('TB_JORNADA.FK_USUARIO')}) as distinct_users_count")
            ->first();

        $domainStats = (clone $baseQuery)
            ->whereNotNull('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->groupBy('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO')
            ->select('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO as domain_id')
            ->selectRaw('COUNT(*) as completed_journeys')
            ->selectRaw("AVG({$grammar->wrap('TB_JORNADA.NU_PONTUACAO_TOTAL')}) as average_score")
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) ((array) $row)['domain_id'] => (array) $row]);

        $domains = KnowledgeDomain::query()
            ->orderBy('PK_DOMINIO_CONHECIMENTO')
            ->get();

        $domainMetrics = [];

        foreach ($domains as $domain) {
            $domainId = (int) $domain->PK_DOMINIO_CONHECIMENTO;
            $stat = $domainStats->get($domainId);
            $completedCount = (int) ($stat['completed_journeys'] ?? 0);

            $domainMetrics[] = [
                'domain_id' => $domainId,
                'domain_name_pt' => $domain->NO_DOMINIO_CONHECIMENTO_PT,
                'domain_name_en' => $domain->NO_DOMINIO_CONHECIMENTO_EN,
                'completed_journeys' => $completedCount,
                'average_score' => $completedCount > 0 && isset($stat['average_score'])
                    ? round((float) $stat['average_score'], 2)
                    : null,
            ];
        }

        $summaryData = $summaryRow !== null ? (array) $summaryRow : [];

        return [
            'total_completed_journeys' => (int) ($summaryData['total_completed_journeys'] ?? 0),
            'distinct_users_count' => (int) ($summaryData['distinct_users_count'] ?? 0),
            'domains' => $domainMetrics,
        ];
    }

    public function listDepartments(): array
    {
        return Usuario::query()
            ->whereNotNull('NO_SETOR')
            ->where('NO_SETOR', '!=', '')
            ->distinct()
            ->orderBy('NO_SETOR')
            ->pluck('NO_SETOR')
            ->map(fn ($department): string => (string) $department)
            ->values()
            ->all();
    }

    public function listUsersWithCompletedResults(): Collection
    {
        return Usuario::query()
            ->whereHas('journeys', function ($query): void {
                $query->whereNotNull('DH_FIM')
                    ->whereNull('DH_CANCELADA_EM');
            })
            ->orderBy('NO_USUARIO')
            ->orderBy('PK_USUARIO')
            ->get(['PK_USUARIO', 'NO_USUARIO', 'DS_EMAIL']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCompletedResultFilters(QueryBuilder $query, array $filters): void
    {
        $query->whereNotNull('TB_JORNADA.DH_FIM')
            ->whereNull('TB_JORNADA.DH_CANCELADA_EM');

        if (isset($filters['user_id'])) {
            $query->where('TB_JORNADA.FK_USUARIO', (int) $filters['user_id']);
        }

        if (! empty($filters['department'])) {
            $query->where('TB_USUARIO.NO_SETOR', trim((string) $filters['department']));
        }

        if (isset($filters['journey_profile_id'])) {
            $query->where('TB_JORNADA.FK_PERFIS_JORNADA', (int) $filters['journey_profile_id']);
        }

        if (! empty($filters['domains']) && is_array($filters['domains'])) {
            $domainIds = array_map('intval', $filters['domains']);
            $query->whereIn('TB_PERFIS_JORNADA.FK_DOMINIO_CONHECIMENTO', $domainIds);
        }

        if (isset($filters['ended_from'])) {
            $query->whereDate('TB_JORNADA.DH_FIM', '>=', (string) $filters['ended_from']);
        }

        if (isset($filters['ended_to'])) {
            $query->whereDate('TB_JORNADA.DH_FIM', '<=', (string) $filters['ended_to']);
        }

        if (! empty($filters['search'])) {
            $this->applyNormalizedSearch($query, trim((string) $filters['search']));
        }
    }

    private function applyNormalizedSearch(QueryBuilder $query, string $search): void
    {
        $escapedSearchTerm = $this->escapeSearchWildcards($this->normalizeSearchTerm($search));
        $pattern = '%'.$escapedSearchTerm.'%';
        $columns = [
            'TB_USUARIO.NO_USUARIO',
            'TB_USUARIO.DS_EMAIL',
            'TB_PERFIS_JORNADA.NO_PERFIS_PT',
            'TB_PERFIS_JORNADA.NO_PERFIS_EN',
        ];

        $connection = $query->getConnection();
        $grammar = $query->getGrammar();

        $query->where(function (QueryBuilder $subQuery) use ($columns, $pattern, $connection, $grammar): void {
            foreach ($columns as $column) {
                $wrappedColumn = $grammar->wrap($column);
                $normalizedColumn = $this->normalizedSearchExpression($connection, $wrappedColumn);
                $subQuery->orWhereRaw("{$normalizedColumn} LIKE ? ESCAPE '\\'", [$pattern]);
            }
        });
    }
}
