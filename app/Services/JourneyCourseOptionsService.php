<?php

namespace App\Services;

use App\Exceptions\NoOngoingJourneyException;
use App\Models\Usuario;
use App\Repositories\Contracts\HigherEducationCourseRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JourneyCourseOptionsService
{
    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly HigherEducationCourseRepositoryInterface $higherEducationCourseRepository,
    ) {}

    public function listAvailableForCurrentJourney(Usuario $authenticatedUser, ?int $typeId): Collection
    {
        if (! $this->journeyRepository->hasOngoingJourney((int) $authenticatedUser->PK_USUARIO)) {
            throw new NoOngoingJourneyException;
        }

        return $this->higherEducationCourseRepository->listAvailable($typeId);
    }
}
