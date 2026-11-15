<?php

namespace App\Repositories\Contracts;

interface SystemSummaryRepositoryInterface
{
    /**
     * @return array{database_connected: bool, database_driver: string, competencies_count: int}
     */
    public function getDatabaseStats(): array;
}
