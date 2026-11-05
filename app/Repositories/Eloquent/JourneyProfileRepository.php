<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class JourneyProfileRepository implements JourneyProfileRepositoryInterface
{
    private const PRIVACY_DOMAIN_ID = 1;

    private const SECURITY_DOMAIN_ID = 2;

    private const JOINT_DOMAIN_ID = 3;

    public function all(int $page, int $perPage, ?string $modality = null, ?string $search = null): LengthAwarePaginator
    {
        $domainIds = match ($modality) {
            'Privacidade' => [self::PRIVACY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            'Segurança' => [self::SECURITY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            default => null,
        };

        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->when($domainIds !== null, fn ($query) => $query->whereIn('FK_DOMINIO_CONHECIMENTO', $domainIds))
            ->when($search !== null, fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('PK_PERFIS_JORNADA')
            ->paginate(perPage: $perPage, page: $page);
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
}
