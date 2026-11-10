<?php

namespace App\Repositories\Eloquent;

use App\Models\Competency;
use App\Repositories\Contracts\SystemSummaryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemSummaryRepository implements SystemSummaryRepositoryInterface
{
    /**
     * @return array{database_connected: bool, database_driver: string, competencies_count: int}
     */
    public function getDatabaseStats(): array
    {
        $connected = false;
        /** @var string $driver */
        $driver = config('database.default', 'unknown');

        try {
            DB::connection()->getPdo();
            $connected = true;
            $driver = DB::connection()->getDriverName();
        } catch (Throwable) {
            $connected = false;
        }

        $competenciesCount = 0;
        if ($connected) {
            try {
                $competenciesCount = Competency::query()->count();
            } catch (Throwable) {
                $competenciesCount = 0;
            }
        }

        return [
            'database_connected' => $connected,
            'database_driver' => $driver,
            'competencies_count' => $competenciesCount,
        ];
    }
}
