<?php

namespace App\Services;

use App\Exceptions\IncompleteJourneyException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JourneyService
{
    private const ADMIN_USER_TYPE = 1;

    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function listForUser(Usuario $authenticatedUser, int $userId, array $filters): LengthAwarePaginator
    {
        $requestedUser = $this->userRepository->findById($userId);

        if ($requestedUser === null) {
            throw new ModelNotFoundException;
        }

        if (! $this->canViewUserJourneys($authenticatedUser, $requestedUser)) {
            throw new AccessDeniedHttpException;
        }

        return $this->journeyRepository->paginatedByUser(
            userId: (int) $requestedUser->PK_USUARIO,
            filters: $filters,
            page: (int) $filters['page'],
            perPage: (int) $filters['per_page'],
        );
    }

    public function findForAuthenticatedUser(Usuario $authenticatedUser, int $journeyId): Journey
    {
        $journey = $this->journeyRepository->findDetailedById($journeyId);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        if (! $this->canViewJourney($authenticatedUser, $journey)) {
            throw new AccessDeniedHttpException;
        }

        $this->ensureCompletedJourneyHasRequiredData($journey);

        return $journey;
    }

    private function canViewUserJourneys(Usuario $authenticatedUser, Usuario $requestedUser): bool
    {
        if ((int) $authenticatedUser->FK_TIPO_USUARIO === self::ADMIN_USER_TYPE) {
            return true;
        }

        return (int) $authenticatedUser->PK_USUARIO === (int) $requestedUser->PK_USUARIO;
    }

    private function canViewJourney(Usuario $authenticatedUser, Journey $journey): bool
    {
        if ((int) $authenticatedUser->FK_TIPO_USUARIO === self::ADMIN_USER_TYPE) {
            return true;
        }

        return (int) $authenticatedUser->PK_USUARIO === (int) $journey->FK_USUARIO;
    }

    private function ensureCompletedJourneyHasRequiredData(Journey $journey): void
    {
        if ($journey->DH_FIM === null) {
            return;
        }

        $missingFields = [];

        foreach ($this->requiredCompletedJourneyRelations() as $relation => $field) {
            if ($journey->{$relation} === null) {
                $missingFields[] = $field;
            }
        }

        if ($journey->higherEducationCourses->isEmpty()) {
            $missingFields[] = 'answers.higher_education_courses';
        }

        if ($journey->journeyProfile !== null && $journey->journeyProfile->knowledgeDomain === null) {
            $missingFields[] = 'journey_profile.knowledge_domain';
        }

        if ($journey->acquiredKnowledges->contains(fn ($acquiredKnowledge): bool => $acquiredKnowledge->competency === null)) {
            $missingFields[] = 'acquired_knowledges.competency';
        }

        if ($missingFields !== []) {
            throw new IncompleteJourneyException($missingFields);
        }
    }

    /**
     * @return array<string, string>
     */
    private function requiredCompletedJourneyRelations(): array
    {
        return [
            'usuario' => 'user',
            'journeyProfile' => 'journey_profile',
            'professionalExperience' => 'answers.professional_experience',
            'profileExperience' => 'answers.profile_experience',
            'englishSubcriterion' => 'answers.english_proficiency',
        ];
    }
}
