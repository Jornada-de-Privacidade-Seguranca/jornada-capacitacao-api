<?php

namespace App\Repositories\Eloquent\Concerns;

use Illuminate\Database\Connection;

trait AppliesNormalizedSearch
{
    protected function normalizeSearchTerm(string $search): string
    {
        $searchTerm = mb_strtolower(trim($search));

        return strtr($searchTerm, $this->accentMap());
    }

    protected function escapeSearchWildcards(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    protected function normalizedSearchExpression(Connection $connection, string $column): string
    {
        return match ($connection->getDriverName()) {
            'pgsql' => $this->postgresqlNormalizedExpression($column),
            'sqlite' => $this->sqliteNormalizedExpression($column),
            default => "lower({$column})",
        };
    }

    protected function postgresqlNormalizedExpression(string $column): string
    {
        $accentMap = $this->accentMap();
        $source = implode('', array_keys($accentMap));
        $target = implode('', array_values($accentMap));

        return "translate(lower({$column}), '{$source}', '{$target}')";
    }

    protected function sqliteNormalizedExpression(string $column): string
    {
        foreach ($this->accentMap() as $character => $replacement) {
            foreach ([$character, mb_strtoupper($character)] as $variant) {
                $column = "replace({$column}, '{$variant}', '{$replacement}')";
            }
        }

        return "lower({$column})";
    }

    /**
     * @return array<string, string>
     */
    protected function accentMap(): array
    {
        $accentMap = [];

        foreach ($this->accentGroups() as $replacement => $characters) {
            foreach (mb_str_split($characters) as $character) {
                $accentMap[$character] = $replacement;
            }
        }

        return $accentMap;
    }

    /**
     * @return array<string, string>
     */
    protected function accentGroups(): array
    {
        return [
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
    }
}
