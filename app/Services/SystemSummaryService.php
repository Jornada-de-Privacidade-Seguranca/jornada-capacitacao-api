<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\Contracts\SystemSummaryRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SystemSummaryService
{
    private const ADMIN_USER_TYPE = 1;

    public function __construct(private readonly SystemSummaryRepositoryInterface $systemSummaryRepository) {}

    /**
     * @param  array{include_database_status?: bool, tag?: string|null}  $options
     * @return array<string, mixed>
     */
    public function getSummary(Usuario $authenticatedUser, array $options = []): array
    {
        $this->authorizeAdministrator($authenticatedUser);

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

    private function authorizeAdministrator(Usuario $authenticatedUser): void
    {
        if ((int) $authenticatedUser->FK_TIPO_USUARIO !== self::ADMIN_USER_TYPE) {
            throw new AccessDeniedHttpException;
        }
    }
}
