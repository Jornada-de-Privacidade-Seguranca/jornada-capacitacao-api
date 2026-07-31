<?php

namespace App\Services;

use App\Exceptions\InactiveUserAccountException;
use App\Exceptions\InvalidLoginCredentialsException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    private const STATUS_ACTIVE = 1;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function authenticate(array $credentials): Usuario
    {
        $email = strtolower(trim($credentials['email']));
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || ! Hash::check($credentials['password'], (string) $user->DS_SENHA)) {
            throw new InvalidLoginCredentialsException();
        }

        if ((int) $user->ST_ATIVO !== self::STATUS_ACTIVE) {
            throw new InactiveUserAccountException();
        }

        return $user;
    }
}
