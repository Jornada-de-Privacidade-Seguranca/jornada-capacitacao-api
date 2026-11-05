<?php

namespace App\Services;

use App\Exceptions\InvalidAccountStateException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserAccountAdministrationService
{
    private const ADMIN_USER_TYPE = 1;

    private const COMMON_USER_TYPE = 2;

    private const ACTIVE_STATUS = 1;

    private const PENDING_STATUS = 2;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function pendingActivationUsers(Usuario $authenticatedUser, int $page, int $perPage): LengthAwarePaginator
    {
        $this->authorizeAdministrator($authenticatedUser);

        return $this->userRepository->pendingActivationPaginated($page, $perPage);
    }

    public function activate(Usuario $authenticatedUser, int $userId): Usuario
    {
        $this->authorizeAdministrator($authenticatedUser);

        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);

            if ((int) $user->ST_ATIVO !== self::PENDING_STATUS || $user->FK_TIPO_USUARIO !== null) {
                throw new InvalidAccountStateException;
            }

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::ACTIVE_STATUS,
                'FK_TIPO_USUARIO' => self::COMMON_USER_TYPE,
            ]);
        });
    }

    public function deactivate(Usuario $authenticatedUser, int $userId): Usuario
    {
        $this->authorizeAdministrator($authenticatedUser);

        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);

            if ((int) $user->ST_ATIVO !== self::ACTIVE_STATUS
                || (int) $user->FK_TIPO_USUARIO !== self::COMMON_USER_TYPE) {
                throw new InvalidAccountStateException;
            }

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::PENDING_STATUS,
            ]);
        });
    }

    private function authorizeAdministrator(Usuario $authenticatedUser): void
    {
        if ((int) $authenticatedUser->FK_TIPO_USUARIO !== self::ADMIN_USER_TYPE) {
            throw new AccessDeniedHttpException;
        }
    }

    private function findUserForUpdate(int $userId): Usuario
    {
        $user = $this->userRepository->findByIdForUpdate($userId);

        if ($user === null) {
            throw new ModelNotFoundException;
        }

        return $user;
    }
}
