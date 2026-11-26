<?php

namespace App\Services;

use App\Exceptions\InvalidAccountStateException;
use App\Models\ProfileType;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UserAccountAdministrationService
{
    private const ACTIVE_STATUS = 1;

    private const PENDING_STATUS = 2;

    private const INACTIVE_STATUS = 0;

    /** Conta criada por convite (TP_ATIVACAO), ativada so quando a pessoa define a senha. */
    private const ADMIN_CREATED = 2;

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function pendingActivationUsers(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->userRepository->pendingActivationPaginated($page, $perPage);
    }

    /**
     * Ativa uma conta pendente (cadastro aguardando aprovacao) como usuario comum, ou reativa uma conta inativa
     * mantendo o perfil de acesso que ela tinha (conta inativa sem perfil recebe o perfil Usuario). Convites que
     * ainda aguardam a senha inicial so sao ativados pela propria pessoa, ao definir a senha.
     */
    public function activate(int $userId): Usuario
    {
        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);
            $status = (int) $user->ST_ATIVO;

            if (! in_array($status, [self::PENDING_STATUS, self::INACTIVE_STATUS], true)
                || $this->isAwaitingInitialPassword($user)) {
                throw new InvalidAccountStateException;
            }

            // Cadastro pendente nunca ganha perfil privilegiado na aprovacao
            $profileTypeId = $status === self::PENDING_STATUS
                ? ProfileType::USER_ID
                : ($user->FK_TIPO_PERFIL ?? ProfileType::USER_ID);

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::ACTIVE_STATUS,
                'FK_TIPO_PERFIL' => $profileTypeId,
            ]);
        });
    }

    /**
     * Desativa um usuario comum ativo (perfil Usuario), sem alterar o perfil de acesso.
     */
    public function deactivate(int $userId): Usuario
    {
        return DB::transaction(function () use ($userId): Usuario {
            $user = $this->findUserForUpdate($userId);

            if ((int) $user->ST_ATIVO !== self::ACTIVE_STATUS
                || (int) $user->FK_TIPO_PERFIL !== ProfileType::USER_ID) {
                throw new InvalidAccountStateException;
            }

            return $this->userRepository->update($user, [
                'ST_ATIVO' => self::INACTIVE_STATUS,
            ]);
        });
    }

    private function isAwaitingInitialPassword(Usuario $user): bool
    {
        return (int) $user->ST_ATIVO === self::PENDING_STATUS
            && (int) $user->TP_ATIVACAO === self::ADMIN_CREATED
            && $user->DS_SENHA === null;
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
