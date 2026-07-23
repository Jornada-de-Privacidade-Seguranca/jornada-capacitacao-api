<?php

namespace App\Services;

use App\Exceptions\UserNotFoundException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserActivationService
{
    private const STATUS_ACTIVE = 1;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function activate(int $userId): Usuario
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return $this->userRepository->update($user, [
            'ST_ATIVO' => self::STATUS_ACTIVE,
        ]);
    }
}
