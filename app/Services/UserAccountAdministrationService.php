<?php

namespace App\Services;

use App\Exceptions\InvalidAccountStateException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UserAccountAdministrationService
{
    private const COMMON_USER_TYPE = 2;

    private const ACTIVE_STATUS = 1;

    private const PENDING_STATUS = 2;

    private const INACTIVE_STATUS = 0;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function pendingActivationUsers(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->userRepository->pendingActivationPaginated($page, $perPage);
    }

    public function activate(int $userId): Usuario
    {
        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);

            if ((int) $user->ST_ATIVO !== self::PENDING_STATUS) {
                throw new InvalidAccountStateException;
            }

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::ACTIVE_STATUS,
                'FK_TIPO_USUARIO' => self::COMMON_USER_TYPE,
            ]);
        });
    }

    public function deactivate(int $userId): Usuario
    {
        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);

            if ((int) $user->ST_ATIVO !== self::ACTIVE_STATUS
                || (int) $user->FK_TIPO_USUARIO !== self::COMMON_USER_TYPE) {
                throw new InvalidAccountStateException;
            }

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::INACTIVE_STATUS,
            ]);
        });
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
