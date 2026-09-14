<?php

namespace App\Services;

use App\Exceptions\IncompleteJourneyException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalExperienceRepositoryInterface;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JourneyService
{
    private const ADMIN_USER_TYPE = 1;

    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly CriterionRepositoryInterface $criterionRepository,
        private readonly ProfessionalExperienceRepositoryInterface $professionalExperienceRepository,
        private readonly SubcriterionRepositoryInterface $subcriterionRepository,
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

    public function getCurrentGeneralDataOptions(Usuario $authenticatedUser): array
    {
        $journey = $this->journeyRepository->findCurrentByUserId((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        $englishCriterionId = $this->criterionRepository->findEnglishCriterionId();

        if ($englishCriterionId === null) {
            throw new ModelNotFoundException;
        }

        $experienceOptions = $this->professionalExperienceRepository
            ->allOrdered()
            ->map(fn ($experience): array => [
                'id' => $experience->PK_EXPERIENCIA_ATUACAO,
                'description_pt' => $experience->DS_EXPERIENCIA_ATUACAO_PT,
                'description_en' => $experience->DS_EXPERIENCIA_ATUACAO_EN,
            ])
            ->values()
            ->all();

        return [
            'journey_id' => $journey->PK_JORNADA,
            'status' => 'ongoing',
            'fields' => [
                'english_level' => [
                    'required' => true,
                    'options' => $this->subcriterionRepository
                        ->allByCriterionId($englishCriterionId)
                        ->map(fn ($subcriterion): array => [
                            'id' => $subcriterion->PK_SUBCRITERIOS,
                            'name_pt' => $subcriterion->NO_SUBCRITERIO_PT,
                            'name_en' => $subcriterion->NO_SUBCRITERIO_EN,
                        ])
                        ->values()
                        ->all(),
                ],
                'professional_experience' => [
                    'required' => true,
                    'options' => $experienceOptions,
                ],
                'profile_experience' => [
                    'required' => true,
                    'options' => $experienceOptions,
                ],
            ],
        ];
    }

    public function updateCurrentGeneralData(Usuario $authenticatedUser, array $data): Journey
    {
        $journey = $this->journeyRepository->findCurrentByUserId((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        $englishSubcriterion = $this->subcriterionRepository->findOrFail((int) $data['english_level_id']);
        $englishCriterionId = $this->criterionRepository->findEnglishCriterionId();

        if ($englishCriterionId === null || (int) $englishSubcriterion->FK_CRITERIOS !== $englishCriterionId) {
            throw ValidationException::withMessages([
                'english_level_id' => ['O nivel de ingles informado nao pertence ao criterio de ingles.'],
            ]);
        }

        return $this->journeyRepository->updateGeneralData($journey, $data);
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
