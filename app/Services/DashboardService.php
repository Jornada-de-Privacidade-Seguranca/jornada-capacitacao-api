<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(private readonly DashboardRepositoryInterface $dashboardRepository)
    {
    }

    public function getDashboardData(Usuario $user): array
    {
        $unfinishedJourney = $this->dashboardRepository->findUnfinishedJourney($user->PK_USUARIO);
        $latestJourney = $this->dashboardRepository->findLatestCompletedJourney($user->PK_USUARIO);

        return [
            'user' => $user->loadMissing('profileType'),
            'unfinished_journey' => $unfinishedJourney,
            'has_unfinished_journey' => $unfinishedJourney !== null,
            'latest_journey' => $latestJourney,
            'recent_journeys' => $this->dashboardRepository->findRecentCompletedJourneys(
                userId: $user->PK_USUARIO,
                exceptJourneyId: $latestJourney?->PK_JORNADA,
                limit: 3,
            ),
        ];
    }
}
