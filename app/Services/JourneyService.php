<?php

namespace App\Services;

use App\Exceptions\CompetencyNotInJourneyProfileException;
use App\Exceptions\IncompleteJourneyException;
use App\Exceptions\JourneyAlreadyFinishedException;
use App\Exceptions\JourneyProfileNotFoundException;
use App\Exceptions\OngoingJourneyExistsException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JourneyService
{
    private const ADMIN_USER_TYPE = 1;

    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly JourneyProfileRepositoryInterface $journeyProfileRepository,
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

    public function startForUser(Usuario $authenticatedUser, int $userId, array $data): Journey
    {
        $requestedUser = $this->userRepository->findById($userId);

        if ($requestedUser === null) {
            throw new ModelNotFoundException;
        }

        if (! $this->canViewUserJourneys($authenticatedUser, $requestedUser)) {
            throw new AccessDeniedHttpException;
        }

        try {
            $this->journeyProfileRepository->findOrFail((int) $data['journey_profile_id']);
        } catch (ModelNotFoundException) {
            throw new JourneyProfileNotFoundException;
        }

        return DB::transaction(function () use ($requestedUser, $data) {
            $this->userRepository->lockForUpdate((int) $requestedUser->PK_USUARIO);

            if ($this->journeyRepository->hasOngoingJourney((int) $requestedUser->PK_USUARIO)) {
                throw new OngoingJourneyExistsException;
            }

            return $this->journeyRepository->create([
                'FK_USUARIO' => $requestedUser->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $data['journey_profile_id'],
                'DH_CRIADO_EM' => now(),
                'DH_FIM' => null,
            ]);
        });
    }

    public function updateAnswers(Usuario $authenticatedUser, int $journeyId, array $data): Journey
    {
        $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

        $this->journeyRepository->update($journey, [
            'FK_EXPERIENCIA_PROFISSIONAL' => $data['professional_experience_id'] ?? $journey->FK_EXPERIENCIA_PROFISSIONAL,
            'FK_ATUACAO_PERFIL' => $data['profile_experience_id'] ?? $journey->FK_ATUACAO_PERFIL,
            'FK_SUBCRITERIOS_INGLES' => $data['english_proficiency_id'] ?? $journey->FK_SUBCRITERIOS_INGLES,
        ]);

        return $this->journeyRepository->findDetailedById($journeyId);
    }

    public function attachCourses(Usuario $authenticatedUser, int $journeyId, array $courseIds): Journey
    {
        $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

        $this->journeyRepository->attachCourses($journey, $courseIds);

        return $this->journeyRepository->findDetailedById($journeyId);
    }

    public function attachCertifications(Usuario $authenticatedUser, int $journeyId, array $certificationIds): Journey
    {
        $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

        $this->journeyRepository->attachCertifications($journey, $certificationIds);

        return $this->journeyRepository->findDetailedById($journeyId);
    }

    public function recordCompetencies(Usuario $authenticatedUser, int $journeyId, array $competencies): Journey
    {
        $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

        $competencyIds = array_column($competencies, 'competency_id');

        if (! $this->journeyRepository->competencyIdsBelongToProfile((int) $journey->FK_PERFIS_JORNADA, $competencyIds)) {
            throw new CompetencyNotInJourneyProfileException;
        }

        $this->journeyRepository->upsertAcquiredKnowledges($journey, $competencies);

        return $this->journeyRepository->findDetailedById($journeyId);
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

    private function resolveJourneyForManagement(Usuario $authenticatedUser, int $journeyId): Journey
    {
        $journey = $this->journeyRepository->findDetailedById($journeyId);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        if (! $this->canViewJourney($authenticatedUser, $journey)) {
            throw new AccessDeniedHttpException;
        }

        if ($journey->DH_FIM !== null) {
            throw new JourneyAlreadyFinishedException;
        }

        return $journey;
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
