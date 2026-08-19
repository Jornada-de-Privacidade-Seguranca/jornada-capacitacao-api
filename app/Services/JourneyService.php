<?php

namespace App\Services;

use App\Exceptions\JourneyProfileNotFoundException;
use App\Exceptions\OngoingJourneyExistsException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
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
        private readonly JourneyProfileRepositoryInterface $journeyProfileRepository,
    ) {
    }

    public function listForUser(Usuario $authenticatedUser, int $userId, array $filters): LengthAwarePaginator
    {
        $requestedUser = $this->userRepository->findById($userId);

        if ($requestedUser === null) {
            throw new ModelNotFoundException();
        }

        if (! $this->canManageUserJourneys($authenticatedUser, $requestedUser)) {
            throw new AccessDeniedHttpException();
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
            throw new ModelNotFoundException();
        }

        if (! $this->canManageUserJourneys($authenticatedUser, $requestedUser)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->journeyProfileRepository->findOrFail((int) $data['journey_profile_id']);
        } catch (ModelNotFoundException) {
            throw new JourneyProfileNotFoundException();
        }

        if ($this->journeyRepository->hasOngoingJourney((int) $requestedUser->PK_USUARIO)) {
            throw new OngoingJourneyExistsException();
        }

        return $this->journeyRepository->create([
            'FK_USUARIO' => $requestedUser->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $data['journey_profile_id'],
            'FK_EXPERIENCIA_PROFISSIONAL' => $data['professional_experience_id'] ?? null,
            'FK_ATUACAO_PERFIL' => $data['profile_experience_id'] ?? null,
            'FK_PROFICIENCIA_INGLES' => $data['english_proficiency_id'] ?? null,
            'FK_CURSO_SUPERIOR' => $data['higher_education_course_id'] ?? null,
            'FK_POS_GRADUACAO' => $data['postgraduate_course_id'] ?? null,
            'DH_CRIADO_EM' => now(),
            'DH_FIM' => null,
        ]);
    }

    private function canManageUserJourneys(Usuario $authenticatedUser, Usuario $requestedUser): bool
    {
        if ((int) $authenticatedUser->FK_TIPO_USUARIO === self::ADMIN_USER_TYPE) {
            return true;
        }

        return (int) $authenticatedUser->PK_USUARIO === (int) $requestedUser->PK_USUARIO;
    }
}
