<?php

namespace App\Services;

use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CurrentJourneyCoursesService
{
    public function __construct(private readonly JourneyRepositoryInterface $journeyRepository) {}

    public function getForAuthenticatedUser(Usuario $authenticatedUser): Journey
    {
        $journey = $this->journeyRepository->findOngoingByUser((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        return $journey;
    }

    public function replaceForAuthenticatedUser(Usuario $authenticatedUser, array $courseIds): Journey
    {
        return DB::transaction(function () use ($authenticatedUser, $courseIds): Journey {
            $journey = $this->getForAuthenticatedUser($authenticatedUser);

            $this->journeyRepository->syncCourses($journey, array_values(array_unique($courseIds)));

            return $journey->load('higherEducationCourses.subcriterion');
        });
    }
}
