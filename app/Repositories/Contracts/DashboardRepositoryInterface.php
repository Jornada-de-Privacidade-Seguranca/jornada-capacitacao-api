<?php

namespace App\Repositories\Contracts;

use App\Models\Journey;
use Illuminate\Database\Eloquent\Collection;

interface DashboardRepositoryInterface
{
    public function findUnfinishedJourney(int $userId): ?Journey;

    public function findLatestCompletedJourney(int $userId): ?Journey;

    public function findRecentCompletedJourneys(int $userId, ?int $exceptJourneyId, int $limit): Collection;
}
