<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class JourneyProfileRepository implements JourneyProfileRepositoryInterface
{
    private const PRIVACY_DOMAIN_ID = 1;

    private const SECURITY_DOMAIN_ID = 2;

    private const JOINT_DOMAIN_ID = 3;

    public function all(
        int $page,
        int $perPage,
        ?string $modality = null,
        ?string $search = null,
        ?array $domainIds = null,
        ?array $userStatuses = null,
        ?int $userId = null,
    ): LengthAwarePaginator {
        $modalityDomainIds = match ($modality) {
            'Privacidade' => [self::PRIVACY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            'Segurança' => [self::SECURITY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            default => null,
        };

        $query = JourneyProfile::query()
            ->with('knowledgeDomain')
            ->whereNotNull('NO_PERFIS_PT')
            ->when($domainIds !== null, fn (Builder $query) => $query->whereIn('FK_DOMINIO_CONHECIMENTO', $domainIds))
            ->when($modalityDomainIds !== null, fn (Builder $query) => $query->whereIn('FK_DOMINIO_CONHECIMENTO', $modalityDomainIds))
            ->when($search !== null, fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('PK_PERFIS_JORNADA');

        $grammar = JourneyProfile::query()->getQuery()->getGrammar();
        $competencyColumn = $grammar->wrap('RL_PERFIS_COMPETENCIAS.FK_COMPETENCIAS');
        $query->select('TB_PERFIS_JORNADA.*')->selectSub(
            DB::table('RL_PERFIS_COMPETENCIAS')
                ->whereColumn('RL_PERFIS_COMPETENCIAS.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
                ->selectRaw('COUNT(DISTINCT '.$competencyColumn.')'),
            'competencies_count',
        );

        $userId ??= 0;
        $query->selectSub(
            $this->userJourneys($userId)->whereNotNull('DH_FIM')->selectRaw('1')->limit(1),
            'completed_journey_exists',
        )->selectSub(
            $this->userJourneys($userId)->whereNull('DH_FIM')->selectRaw('1')->limit(1),
            'pending_journey_exists',
        )->selectSub(
            $this->userJourneys($userId)
                ->whereNotNull('DH_FIM')
                ->orderByDesc('DH_FIM')
                ->orderByDesc('PK_JORNADA')
                ->limit(1)
                ->select('NU_PONTUACAO_TOTAL'),
            'last_score',
        );

        if ($userStatuses !== null) {
            $query->where(function (Builder $query) use ($userStatuses, $userId): void {
                if (in_array('completed', $userStatuses, true)) {
                    $query->orWhereExists($this->userJourneys($userId)->whereNotNull('DH_FIM')->selectRaw('1'));
                }

                if (in_array('pending', $userStatuses, true)) {
                    $query->orWhereExists($this->userJourneys($userId)->whereNull('DH_FIM')->selectRaw('1'));
                }

                if (in_array('not_done', $userStatuses, true)) {
                    $query->orWhereNotExists($this->userJourneys($userId)->selectRaw('1'));
                }
            });
        }

        return $query->paginate(perPage: $perPage, page: $page);
    }

    private function userJourneys(int $userId): QueryBuilder
    {
        return DB::table('TB_JORNADA')
            ->whereColumn('TB_JORNADA.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
            ->where('TB_JORNADA.FK_USUARIO', $userId);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $searchTerm = $this->normalizeSearchTerm($search);
        $escapedSearchTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
        $pattern = '%'.$escapedSearchTerm.'%';
        $columns = ['NO_PERFIS_PT', 'NO_PERFIS_EN', 'DS_PERFIS_PT', 'DS_PERFIS_EN'];

        $query->where(function (Builder $query) use ($columns, $pattern): void {
            foreach ($columns as $column) {
                $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);
                $normalizedColumn = $this->normalizedSearchExpression($wrappedColumn);
                $query->orWhereRaw("{$normalizedColumn} LIKE ? ESCAPE '\\'", [$pattern]);
            }
        });
    }

    private function normalizedSearchExpression(string $column): string
    {
        return match (JourneyProfile::query()->getConnection()->getDriverName()) {
            'pgsql' => $this->postgresqlNormalizedExpression($column),
            'sqlite' => $this->sqliteNormalizedExpression($column),
            default => "lower({$column})",
        };
    }

    private function normalizeSearchTerm(string $search): string
    {
        $searchTerm = mb_strtolower(trim($search));

        return strtr($searchTerm, $this->accentMap());
    }

    private function postgresqlNormalizedExpression(string $column): string
    {
        $accentMap = $this->accentMap();
        $source = implode('', array_keys($accentMap));
        $target = implode('', array_values($accentMap));

        return "translate(lower({$column}), '{$source}', '{$target}')";
    }

    private function sqliteNormalizedExpression(string $column): string
    {
        foreach ($this->accentMap() as $character => $replacement) {
            foreach ([$character, mb_strtoupper($character)] as $variant) {
                $column = "replace({$column}, '{$variant}', '{$replacement}')";
            }
        }

        return "lower({$column})";
    }

    private function accentMap(): array
    {
        $accentMap = [];

        foreach ($this->accentGroups() as $replacement => $characters) {
            foreach (mb_str_split($characters) as $character) {
                $accentMap[$character] = $replacement;
            }
        }

        return $accentMap;
    }

    private function accentGroups(): array
    {
        $groups = [
            'a' => 'áàâãäåāăą',
            'c' => 'çćč',
            'd' => 'ďđ',
            'e' => 'éèêëēėę',
            'i' => 'íìîïī',
            'l' => 'ł',
            'n' => 'ñń',
            'o' => 'óòôõöø',
            'r' => 'ř',
            's' => 'śšş',
            't' => 'ť',
            'u' => 'úùûüū',
            'y' => 'ýÿ',
            'z' => 'žźż',
        ];

        return $groups;
    }

    public function findOrFail(int $id): JourneyProfile
    {
        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->findOrFail($id);
    }

    public function findWithCountsOrFail(int $id): JourneyProfile
    {
        $grammar = JourneyProfile::query()->getQuery()->getGrammar();
        $competencyColumn = $grammar->wrap('RL_PERFIS_COMPETENCIAS.FK_COMPETENCIAS');
        $knowledgeAreaColumn = $grammar->wrap('TB_COMPETENCIAS.FK_AREA_CONHECIMENTO');

        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->select('TB_PERFIS_JORNADA.*')
            ->selectSub(
                DB::table('RL_PERFIS_COMPETENCIAS')
                    ->whereColumn('RL_PERFIS_COMPETENCIAS.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
                    ->selectRaw('COUNT(DISTINCT '.$competencyColumn.')'),
                'competencies_count',
            )
            ->selectSub(
                DB::table('RL_PERFIS_COMPETENCIAS')
                    ->join('TB_COMPETENCIAS', 'TB_COMPETENCIAS.PK_COMPETENCIAS', '=', 'RL_PERFIS_COMPETENCIAS.FK_COMPETENCIAS')
                    ->whereColumn('RL_PERFIS_COMPETENCIAS.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
                    ->selectRaw('COUNT(DISTINCT '.$knowledgeAreaColumn.') + CASE WHEN COUNT(*) > COUNT('.$knowledgeAreaColumn.') THEN 1 ELSE 0 END'),
                'knowledge_areas_count',
            )
            ->findOrFail($id);
    }
}
