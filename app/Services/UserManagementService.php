<?php

namespace App\Services;

use App\Exceptions\EmailAlreadyInUseException;
use App\Exceptions\LastAdministratorRequiredException;
use App\Exceptions\SelfAccountOperationNotAllowedException;
use App\Models\ProfileType;
use App\Models\ProfileTypeChange;
use App\Models\Usuario;
use App\Repositories\Contracts\AccessProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    private const ACTIVE_STATUS = 1;

    /** Campo da requisicao => coluna de TB_USUARIO. */
    private const COLUMN_BY_FIELD = [
        'name' => 'NO_USUARIO',
        'email' => 'DS_EMAIL',
        'organization' => 'NO_ORGAO_EMPRESA',
        'department' => 'NO_SETOR',
        'registration_number' => 'NU_MATRICULA',
    ];

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccessProfileRepositoryInterface $accessProfileRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUsers(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->userRepository->paginatedUsers($filters, $page, $perPage);
    }

    public function findUser(int $userId): Usuario
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new ModelNotFoundException;
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $userId, array $data): Usuario
    {
        $user = $this->findUser($userId);
        $attributes = [];

        foreach (self::COLUMN_BY_FIELD as $field => $column) {
            if (array_key_exists($field, $data)) {
                $attributes[$column] = $data[$field];
            }
        }

        $email = $attributes['DS_EMAIL'] ?? null;
        if ($email !== null && $this->userRepository->existsByEmailExcept((string) $email, (int) $user->PK_USUARIO)) {
            throw new EmailAlreadyInUseException;
        }

        try {
            return $this->userRepository->update($user, $attributes)->loadCount('completedJourneys');
        } catch (QueryException $exception) {
            if ($email !== null && $this->isUniqueEmailViolation($exception)) {
                throw new EmailAlreadyInUseException(previous: $exception);
            }

            throw $exception;
        }
    }

    public function changeAccessProfile(Usuario $authenticatedUser, int $userId, int $newProfileTypeId): Usuario
    {
        if ((int) $authenticatedUser->PK_USUARIO === $userId) {
            throw new SelfAccountOperationNotAllowedException;
        }

        return DB::transaction(function () use ($authenticatedUser, $userId, $newProfileTypeId): Usuario {
            $this->userRepository->lockActiveAdministrators();
            $this->accessProfileRepository->findOrFail($newProfileTypeId);

            $user = $this->userRepository->findByIdForUpdate($userId);

            if ($user === null) {
                throw new ModelNotFoundException;
            }

            $previousProfileTypeId = $user->FK_TIPO_PERFIL !== null ? (int) $user->FK_TIPO_PERFIL : null;

            if ($previousProfileTypeId === $newProfileTypeId) {
                return $user->load('profileType')->loadCount('completedJourneys');
            }

            if ($previousProfileTypeId === ProfileType::ADMIN_ID
                && $newProfileTypeId !== ProfileType::ADMIN_ID
                && (int) $user->ST_ATIVO === self::ACTIVE_STATUS
                && $this->userRepository->countActiveAdministratorsExcept($userId) === 0) {
                throw new LastAdministratorRequiredException;
            }

            $updatedUser = $this->userRepository->update($user, [
                'FK_TIPO_PERFIL' => $newProfileTypeId,
            ]);

            $this->accessProfileRepository->recordProfileChange([
                'FK_USUARIO_ALTERADO' => $user->PK_USUARIO,
                'FK_TIPO_PERFIL_ANTERIOR' => $previousProfileTypeId,
                'FK_TIPO_PERFIL_NOVO' => $newProfileTypeId,
                'FK_USUARIO_ADM' => $authenticatedUser->PK_USUARIO,
                'DH_REGISTRO' => now(),
            ]);

            return $updatedUser->loadCount('completedJourneys');
        });
    }

    /**
     * @return Collection<int, ProfileTypeChange>
     */
    public function accessProfileHistory(int $userId): Collection
    {
        $user = $this->findUser($userId);

        return $this->accessProfileRepository->historyForUser((int) $user->PK_USUARIO);
    }

    /**
     * @return Collection<int, ProfileType>
     */
    public function listAccessProfiles(): Collection
    {
        return $this->accessProfileRepository->allWithPermissions();
    }

    public function deleteUser(Usuario $authenticatedUser, int $userId): void
    {
        if ((int) $authenticatedUser->PK_USUARIO === $userId) {
            throw new SelfAccountOperationNotAllowedException;
        }

        DB::transaction(function () use ($userId): void {
            $this->userRepository->lockActiveAdministrators();

            $user = $this->userRepository->findByIdForUpdate($userId);

            if ($user === null) {
                throw new ModelNotFoundException;
            }

            if ((int) $user->FK_TIPO_PERFIL === ProfileType::ADMIN_ID
                && (int) $user->ST_ATIVO === self::ACTIVE_STATUS
                && $this->userRepository->countActiveAdministratorsExcept($userId) === 0) {
                throw new LastAdministratorRequiredException;
            }

            $this->userRepository->softDelete($user);
        });
    }

    private function isUniqueEmailViolation(QueryException $exception): bool
    {
        $code = (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($code, ['23000', '23505'], true)
            || (str_contains($message, 'unique') && str_contains($message, 'email'));
    }
}
