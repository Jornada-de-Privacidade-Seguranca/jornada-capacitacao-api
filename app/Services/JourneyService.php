<?php

namespace App\Services;

use App\Exceptions\CompetencyNotInJourneyProfileException;
use App\Exceptions\IncompleteJourneyException;
use App\Exceptions\JourneyAlreadyFinishedException;
use App\Exceptions\JourneyProfileNotFoundException;
use App\Exceptions\JourneyProfileSelectionException;
use App\Exceptions\NoOngoingJourneyException;
use App\Exceptions\OngoingJourneyExistsException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalExperienceRepositoryInterface;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
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
        private readonly JourneyProfileRepositoryInterface $journeyProfileRepository,
        private readonly JourneyCalculationService $journeyCalculationService,
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

            $user = $this->userRepository->findById((int) $requestedUser->PK_USUARIO);
            $journeyProfileId = (int) $data['journey_profile_id'];
            $hasJourneyHistory = $this->journeyRepository->hasAnyJourney((int) $requestedUser->PK_USUARIO);

            if (! $hasJourneyHistory) {
                $selectedJourneyProfileId = (int) ($user?->FK_PERFIL_JORNADA ?? 0);

                if ($selectedJourneyProfileId === 0) {
                    throw new JourneyProfileSelectionException(JourneyProfileSelectionException::REQUIRED);
                }

                if ($selectedJourneyProfileId !== $journeyProfileId) {
                    throw new JourneyProfileSelectionException(JourneyProfileSelectionException::MISMATCH);
                }
            }

            if ($this->journeyRepository->hasOngoingJourney((int) $requestedUser->PK_USUARIO)) {
                throw new OngoingJourneyExistsException;
            }

            return $this->journeyRepository->create([
                'FK_USUARIO' => $requestedUser->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $journeyProfileId,
                'DH_CRIADO_EM' => now(),
                'DH_FIM' => null,
            ]);
        });
    }

    public function selectInitialJourneyProfile(Usuario $authenticatedUser, int $journeyProfileId): Usuario
    {
        $this->journeyProfileRepository->findOrFail($journeyProfileId);

        return DB::transaction(function () use ($authenticatedUser, $journeyProfileId): Usuario {
            $this->userRepository->lockForUpdate((int) $authenticatedUser->PK_USUARIO);
            $user = $this->userRepository->findById((int) $authenticatedUser->PK_USUARIO);

            if ($user?->FK_PERFIL_JORNADA !== null && $this->journeyRepository->hasJourneyForProfile(
                (int) $authenticatedUser->PK_USUARIO,
                (int) $user->FK_PERFIL_JORNADA,
            )) {
                throw new JourneyProfileSelectionException(JourneyProfileSelectionException::LOCKED);
            }

            return $this->userRepository->update($user ?? $authenticatedUser, [
                'FK_PERFIL_JORNADA' => $journeyProfileId,
            ]);
        });
    }

    public function updateAnswers(Usuario $authenticatedUser, int $journeyId, array $data): Journey
    {
        return DB::transaction(function () use ($authenticatedUser, $journeyId, $data): Journey {
            $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

            $this->journeyRepository->update($journey, [
                'FK_EXPERIENCIA_PROFISSIONAL' => $data['professional_experience_id'] ?? $journey->FK_EXPERIENCIA_PROFISSIONAL,
                'FK_ATUACAO_PERFIL' => $data['profile_experience_id'] ?? $journey->FK_ATUACAO_PERFIL,
                'FK_SUBCRITERIOS_INGLES' => $data['english_proficiency_id'] ?? $journey->FK_SUBCRITERIOS_INGLES,
            ]);

            return $this->journeyRepository->findDetailedById($journeyId);
        });
    }

    public function attachCourses(Usuario $authenticatedUser, int $journeyId, array $courseIds): Journey
    {
        return DB::transaction(function () use ($authenticatedUser, $journeyId, $courseIds): Journey {
            $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

            $this->journeyRepository->attachCourses($journey, $courseIds);

            return $this->journeyRepository->findDetailedById($journeyId);
        });
    }

    public function attachCertifications(Usuario $authenticatedUser, int $journeyId, array $certificationIds): Journey
    {
        return DB::transaction(function () use ($authenticatedUser, $journeyId, $certificationIds): Journey {
            $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

            $this->journeyRepository->attachCertifications($journey, $certificationIds);

            return $this->journeyRepository->findDetailedById($journeyId);
        });
    }

    public function recordCompetencies(Usuario $authenticatedUser, int $journeyId, array $competencies): Journey
    {
        return DB::transaction(function () use ($authenticatedUser, $journeyId, $competencies): Journey {
            $journey = $this->resolveJourneyForManagement($authenticatedUser, $journeyId);

            $competencyIds = array_column($competencies, 'competency_id');

            if (! $this->journeyRepository->competencyIdsBelongToProfile((int) $journey->FK_PERFIS_JORNADA, $competencyIds)) {
                throw new CompetencyNotInJourneyProfileException;
            }

            $this->journeyRepository->upsertAcquiredKnowledges($journey, $competencies);

            return $this->journeyRepository->findDetailedById($journeyId);
        });
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

    public function finalizeCurrent(Usuario $authenticatedUser): Journey
    {
        return DB::transaction(function () use ($authenticatedUser): Journey {
            $journey = $this->journeyRepository->findLatestByUserForUpdate(
                (int) $authenticatedUser->PK_USUARIO,
            );

            if ($journey === null) {
                throw new ModelNotFoundException;
            }

            if ($journey->DH_FIM !== null) {
                throw new JourneyAlreadyFinishedException;
            }

            $missingFields = $this->missingRequiredFields($journey);

            if ($missingFields !== []) {
                throw new IncompleteJourneyException($missingFields);
            }

            try {
                $totalScore = $this->journeyCalculationService->calculateForJourney($journey);
                $scoreBreakdown = $this->journeyCalculationService->calculateScoreBreakdownForJourney($journey);
            } catch (InvalidArgumentException) {
                throw new IncompleteJourneyException(['answers.profile_experience']);
            }

            $this->journeyRepository->update($journey, [
                'NU_PONTUACAO_TOTAL' => round($totalScore, 3),
                'JS_SCORE_BREAKDOWN' => $scoreBreakdown,
                'DH_FIM' => now(),
            ]);

            return $journey->refresh();
        });
    }

    public function getCurrentGeneralDataOptions(Usuario $authenticatedUser): array
    {
        $journey = $this->journeyRepository->findCurrentByUserId((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new NoOngoingJourneyException;
        }

        $englishCriterionId = $this->criterionRepository->findEnglishCriterionId();

        if ($englishCriterionId === null) {
            throw new RuntimeException('English proficiency criterion is not configured.');
        }

        return [
            'journey' => $journey,
            'english_levels' => $this->subcriterionRepository->allByCriterionId($englishCriterionId),
            'experience_options' => $this->professionalExperienceRepository->allOrdered(),
        ];
    }

    public function updateCurrentGeneralData(Usuario $authenticatedUser, array $data): Journey
    {
        $journey = $this->journeyRepository->findCurrentByUserId((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new NoOngoingJourneyException;
        }

        if (array_key_exists('english_level_id', $data)) {
            $this->ensureEnglishLevel((int) $data['english_level_id']);
        }

        return $this->journeyRepository->updateGeneralData($journey, $data);
    }

    private function ensureEnglishLevel(int $englishLevelId): void
    {
        $englishSubcriterion = $this->subcriterionRepository->findOrFail($englishLevelId);
        $englishCriterionId = $this->criterionRepository->findEnglishCriterionId();

        if ($englishCriterionId === null) {
            throw new RuntimeException('English proficiency criterion is not configured.');
        }

        if ((int) $englishSubcriterion->FK_CRITERIOS !== $englishCriterionId) {
            throw ValidationException::withMessages([
                'english_level_id' => ['O nivel de ingles informado nao pertence ao criterio de ingles.'],
            ]);
        }
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

    private function resolveJourneyForManagement(
        Usuario $authenticatedUser,
        int $journeyId,
    ): Journey {
        $journey = $this->journeyRepository->findDetailedByIdForUpdate($journeyId);

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

        $missingFields = $this->missingRequiredFields($journey);

        if ($missingFields !== []) {
            throw new IncompleteJourneyException($missingFields);
        }
    }

    /**
     * @return array<int, string>
     */
    private function missingRequiredFields(Journey $journey): array
    {
        $missingFields = [];

        foreach ($this->requiredCompletedJourneyRelations() as $relation => $field) {
            if ($journey->{$relation} === null) {
                $missingFields[] = $field;
            }
        }

        if ($journey->journeyProfile !== null && $journey->journeyProfile->knowledgeDomain === null) {
            $missingFields[] = 'journey_profile.knowledge_domain';
        }

        $hasBrokenCompetency = $journey->acquiredKnowledges
            ->contains(fn ($knowledge): bool => $knowledge->competency === null);

        if ($hasBrokenCompetency) {
            $missingFields[] = 'acquired_knowledges.competency';
        }

        if ($journey->journeyProfile !== null && ! $hasBrokenCompetency) {
            $expectedIds = $journey->journeyProfile->competencies
                ->pluck('PK_COMPETENCIAS')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values();
            $answeredIds = $journey->acquiredKnowledges
                ->pluck('FK_COMPETENCIAS')
                ->filter(fn ($id): bool => $id !== null)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            if ($expectedIds->isEmpty() || $expectedIds->all() !== $answeredIds->all()) {
                $missingFields[] = 'answers.competencies';
            }
        }

        return array_values(array_unique($missingFields));
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
