<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class JourneyCompetencyScriptLoader
{
    private const EXPECTED_COMPETENCY_COUNT = 256;

    private const EXPECTED_PROFILE_COMPETENCY_COUNT = 2556;

    private const SCRIPTS = [
        'V3__Inserts_competencias_privacidade.sql',
        'V4__Inserts_competencias_seguranca_informacao.sql',
        'V5__Inserts_rl_perfis_competencias_privacidade.sql',
        'V6__Inserts_rl_perfis_competencias_seguranca_informacao.sql',
    ];

    public static function load(): void
    {
        self::synchronizeKnowledgeAreas();

        $competencyCount = DB::table('TB_COMPETENCIAS')->count();
        $profileCompetencyCount = DB::table('RL_PERFIS_COMPETENCIAS')->count();

        if (
            $competencyCount >= self::EXPECTED_COMPETENCY_COUNT
            && $profileCompetencyCount >= self::EXPECTED_PROFILE_COMPETENCY_COUNT
        ) {
            return;
        }

        if ($competencyCount !== 0 || $profileCompetencyCount !== 0) {
            throw new RuntimeException(sprintf(
                'The fixed journey competency data is incomplete (%d competencies, %d profile links).',
                $competencyCount,
                $profileCompetencyCount,
            ));
        }

        DB::transaction(function (): void {
            self::resetCompetencySequence();

            foreach (self::SCRIPTS as $script) {
                $path = base_path('scripts/'.$script);
                $sql = file_get_contents($path);

                if ($sql === false) {
                    throw new RuntimeException(sprintf('Could not read fixed journey data script: %s.', $script));
                }

                DB::unprepared(self::quoteLegacyInsertIdentifiers($sql));
            }

            $competencyCount = DB::table('TB_COMPETENCIAS')->count();
            $profileCompetencyCount = DB::table('RL_PERFIS_COMPETENCIAS')->count();

            if (
                $competencyCount !== self::EXPECTED_COMPETENCY_COUNT
                || $profileCompetencyCount !== self::EXPECTED_PROFILE_COMPETENCY_COUNT
            ) {
                throw new RuntimeException(sprintf(
                    'Expected %d fixed competencies and %d profile links, loaded %d and %d.',
                    self::EXPECTED_COMPETENCY_COUNT,
                    self::EXPECTED_PROFILE_COMPETENCY_COUNT,
                    $competencyCount,
                    $profileCompetencyCount,
                ));
            }
        });
    }

    private static function resetCompetencySequence(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('SELECT setval(\'"TB_COMPETENCIAS_PK_COMPETENCIAS_seq"\', 1, false)');

            return;
        }

        if ($driver === 'sqlite') {
            DB::table('sqlite_sequence')
                ->where('name', 'TB_COMPETENCIAS')
                ->delete();
        }
    }

    private static function synchronizeKnowledgeAreas(): void
    {
        $sql = file_get_contents(base_path('database/sql/jornada_fixed_data.sql'));

        if ($sql === false) {
            throw new RuntimeException('Could not read fixed journey reference data.');
        }

        $start = strpos($sql, '-- Insert TB_AREA_CONHECIMENTO');
        $end = strpos($sql, '-- Insert TB_DIFICULDADE');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Could not find the fixed journey knowledge area section.');
        }

        $section = substr($sql, $start, $end - $start);
        preg_match_all(
            '/INSERT INTO "TB_AREA_CONHECIMENTO"\s*\(([^)]*)\)\s*VALUES\s*\(([^;]*)\);/i',
            $section,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $index => $match) {
            $columns = array_map(
                static fn (string $column): string => trim($column, " \t\n\r\0\x0B\""),
                explode(',', $match[1]),
            );
            $values = self::splitSqlValues($match[2]);

            if (count($columns) !== count($values)) {
                throw new RuntimeException('A fixed journey knowledge area row has an invalid column count.');
            }

            DB::table('TB_AREA_CONHECIMENTO')->updateOrInsert(
                ['PK_AREA_CONHECIMENTO' => $index + 1],
                array_combine($columns, $values),
            );
        }

        if (count($matches) !== 19) {
            throw new RuntimeException(sprintf(
                'Expected 19 fixed knowledge areas, found %d.',
                count($matches),
            ));
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'SELECT setval(\'"TB_AREA_CONHECIMENTO_PK_AREA_CONHECIMENTO_seq"\', '
                .'(SELECT COALESCE(MAX("PK_AREA_CONHECIMENTO"), 1) FROM "TB_AREA_CONHECIMENTO"), true)',
            );
        }
    }

    /**
     * @return array<int, string|null>
     */
    private static function splitSqlValues(string $values): array
    {
        $parts = [];
        $current = '';
        $insideString = false;

        for ($index = 0, $length = strlen($values); $index < $length; $index++) {
            $character = $values[$index];

            if ($character === "'" && $insideString && ($values[$index + 1] ?? null) === "'") {
                $current .= "''";
                $index++;

                continue;
            }

            if ($character === "'") {
                $insideString = ! $insideString;
                $current .= $character;

                continue;
            }

            if ($character === ',' && ! $insideString) {
                $parts[] = self::parseSqlValue(trim($current));
                $current = '';

                continue;
            }

            $current .= $character;
        }

        $parts[] = self::parseSqlValue(trim($current));

        return $parts;
    }

    private static function parseSqlValue(string $value): ?string
    {
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace("''", "'", substr($value, 1, -1));
        }

        return strtoupper($value) === 'NULL' ? null : $value;
    }

    private static function quoteLegacyInsertIdentifiers(string $sql): string
    {
        return preg_replace_callback(
            '/(INSERT\s+INTO\s+)public\.([a-z0-9_]+)\s*\(([^)]*)\)/i',
            static function (array $matches): string {
                $table = strtoupper($matches[2]);
                $columns = array_map(
                    static fn (string $column): string => '"'.strtoupper(trim($column)).'"',
                    explode(',', $matches[3]),
                );

                return $matches[1].'"'.$table.'" ('.implode(', ', $columns).')';
            },
            $sql,
        ) ?? $sql;
    }
}
