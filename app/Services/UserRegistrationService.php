<?php

namespace App\Services;

use App\Exceptions\DuplicateUserRegistrationException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRegistrationService
{
    private const STATUS_PENDING_ACTIVATION = 2;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function register(array $data): Usuario
    {
        $email = Str::lower(trim($data['email']));

        if ($this->userRepository->existsByEmail($email)) {
            throw new DuplicateUserRegistrationException();
        }

        $registrationNumber = isset($data['registration_number']) ? trim((string) $data['registration_number']) : null;

        return $this->userRepository->create([
            'FK_TIPO_PERFIL' => $data['profile_type_id'] ?? null,
            'FK_TIPO_USUARIO' => null,
            'NO_USUARIO' => trim($data['name']),
            'NO_ORGAO_EMPRESA' => trim($data['organization_company']),
            'NO_SETOR' => trim($data['department']),
            'NU_MATRICULA' => $registrationNumber !== '' ? $registrationNumber : null,
            'DS_EMAIL' => $email,
            'DS_SENHA' => Hash::make($data['password']),
            'ST_ATIVO' => self::STATUS_PENDING_ACTIVATION,
        ]);
    }
}
