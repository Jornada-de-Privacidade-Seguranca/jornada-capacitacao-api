<?php

namespace App\Services;

use App\Exceptions\NoOngoingJourneyException;
use App\Models\Usuario;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\HigherEducationCourseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JourneyCourseOptionsService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly HigherEducationCourseRepositoryInterface $higherEducationCourseRepository,
    ) {}

    public function listAvailableForCurrentJourney(Usuario $authenticatedUser, ?int $typeId): Collection
    {
        $ongoingJourney = $this->dashboardRepository->findUnfinishedJourney((int) $authenticatedUser->PK_USUARIO);

        if ($ongoingJourney === null) {
            throw new NoOngoingJourneyException;
        }

        return $this->higherEducationCourseRepository->listAvailable($typeId);
    }
}
