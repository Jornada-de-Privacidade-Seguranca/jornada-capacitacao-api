<?php

namespace App\Services;

use App\Repositories\Contracts\SystemSummaryRepositoryInterface;

class SystemSummaryService
{
    public function __construct(private readonly SystemSummaryRepositoryInterface $systemSummaryRepository) {}

    /**
     * @param  array{include_database_status?: bool, tag?: string|null}  $options
     * @return array<string, mixed>
     */
    public function getSummary(array $options = []): array
    {
        $includeDatabaseStatus = (bool) ($options['include_database_status'] ?? true);
        /** @var string|null $tag */
        $tag = $options['tag'] ?? null;

        $dbStats = $this->systemSummaryRepository->getDatabaseStats();

        $databaseInfo = [
            'competencies_count' => $dbStats['competencies_count'],
        ];

        if ($includeDatabaseStatus) {
            $databaseInfo['connected'] = $dbStats['database_connected'];
            $databaseInfo['driver'] = $dbStats['database_driver'];
        }

        return [
            'app_name' => config('app.name', 'Jornada de Capacitacao API'),
            'environment' => config('app.env', 'production'),
            'tag' => $tag,
            'database' => $databaseInfo,
        ];
    }
}
