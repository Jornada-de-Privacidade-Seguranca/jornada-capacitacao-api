<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * scripts/Script_Insert_Jornada.sql e database/sql/jornada_fixed_data.sql nao sao
 * executados pela aplicacao (nao ha migration/seeder que os referencie), mas o
 * primeiro e mantido manualmente como script de referencia para criacao direta do
 * banco. Um review anterior encontrou o script legado com os inserts de
 * TB_SUBCRITERIOS sem FK_CRITERIOS, porque nada garantia que os dois arquivos
 * ficassem alinhados. Este teste compara os dois textualmente para pegar essa
 * divergencia cedo, sem depender de banco.
 */
class LegacyJornadaScriptsConsistencyTest extends TestCase
{
    public function test_legacy_insert_script_matches_fixed_data_criterion_mapping(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $fixedData = file_get_contents($repositoryRoot.'/database/sql/jornada_fixed_data.sql');
        $legacyScript = file_get_contents($repositoryRoot.'/scripts/Script_Insert_Jornada.sql');

        $fixedCriteria = $this->extractCriteria($fixedData);
        $legacyCriteria = $this->extractCriteria($legacyScript);

        $fixedMap = $this->resolveSubcriterionCriterionMap($fixedData, $fixedCriteria);
        $legacyMap = $this->resolveSubcriterionCriterionMap($legacyScript, $legacyCriteria);

        $this->assertNotEmpty($fixedMap, 'Nao foi possivel extrair subcriterios de database/sql/jornada_fixed_data.sql.');
        $this->assertNotEmpty($legacyMap, 'Nao foi possivel extrair subcriterios de scripts/Script_Insert_Jornada.sql.');

        foreach ($fixedMap as $subcriterionName => $criterionName) {
            $this->assertArrayHasKey(
                $subcriterionName,
                $legacyMap,
                "O subcriterio '{$subcriterionName}' existe em jornada_fixed_data.sql mas nao em Script_Insert_Jornada.sql.",
            );

            $this->assertSame(
                $criterionName,
                $legacyMap[$subcriterionName],
                "O subcriterio '{$subcriterionName}' deveria pertencer ao criterio '{$criterionName}' (conforme jornada_fixed_data.sql), ".
                "mas Script_Insert_Jornada.sql aponta para '{$legacyMap[$subcriterionName]}'. Verifique o FK_CRITERIOS/fk_criterios dos inserts.",
            );
        }

        foreach (array_keys($legacyMap) as $subcriterionName) {
            $this->assertArrayHasKey(
                $subcriterionName,
                $fixedMap,
                "O subcriterio '{$subcriterionName}' existe em Script_Insert_Jornada.sql mas nao em jornada_fixed_data.sql.",
            );
        }
    }

    /**
     * @return array<int, string> nomes de NO_CRITERIO_PT, na ordem de insercao (indice 0 = PK 1).
     */
    private function extractCriteria(string $sqlContent): array
    {
        $rows = $this->extractInsertRows($sqlContent, 'TB_CRITERIOS');

        return array_map(
            fn (array $row) => $this->unquote($this->columnValue($row, 'no_criterio_pt')),
            $rows,
        );
    }

    /**
     * @param  array<int, string>  $criteria  retornado por extractCriteria(), na mesma ordem/arquivo.
     * @return array<string, string|null> nome do subcriterio => nome do criterio pai.
     */
    private function resolveSubcriterionCriterionMap(string $sqlContent, array $criteria): array
    {
        $map = [];

        foreach ($this->extractInsertRows($sqlContent, 'TB_SUBCRITERIOS') as $row) {
            $subcriterionName = $this->unquote($this->columnValue($row, 'no_subcriterio_pt'));
            $fkValue = $this->columnValue($row, 'fk_criterios');
            $criterionIndex = $fkValue !== null ? ((int) trim($fkValue)) - 1 : null;

            $map[$subcriterionName] = $criterionIndex !== null && isset($criteria[$criterionIndex])
                ? $criteria[$criterionIndex]
                : null;
        }

        return $map;
    }

    /**
     * @return array<int, array{columns: array<int, string>, values: array<int, string>}>
     */
    private function extractInsertRows(string $sqlContent, string $table): array
    {
        $pattern = '/insert\s+into\s+(?:public\.)?"?'.$table.'"?\s*\(([^)]*)\)\s*values\s*\(([^;]*)\)\s*;/i';
        preg_match_all($pattern, $sqlContent, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $columns = array_map(
                fn (string $column) => strtolower(trim($column, " \t\"'")),
                $this->splitTopLevelCommas($match[1]),
            );

            $rows[] = [
                'columns' => $columns,
                'values' => $this->splitTopLevelCommas($match[2]),
            ];
        }

        return $rows;
    }

    /**
     * @param  array{columns: array<int, string>, values: array<int, string>}  $row
     */
    private function columnValue(array $row, string $column): ?string
    {
        $index = array_search($column, $row['columns'], true);

        return $index === false ? null : $row['values'][$index];
    }

    /**
     * @return array<int, string>
     */
    private function splitTopLevelCommas(string $value): array
    {
        $parts = [];
        $current = '';
        $inString = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === "'") {
                if ($inString && ($i + 1) < $length && $value[$i + 1] === "'") {
                    $current .= "''";
                    $i++;

                    continue;
                }

                $inString = ! $inString;
                $current .= $char;

                continue;
            }

            if ($char === ',' && ! $inString) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private function unquote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace("''", "'", substr($value, 1, -1));
        }

        return $value;
    }
}
