<?php

namespace App\Services;

use App\Exceptions\AccountAlreadyExistsException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService
{
    private const STATUS_PENDING_ACTIVATION = 2;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function register(array $data): Usuario
    {
        $email = strtolower(trim($data['email']));

        if ($this->userRepository->existsByEmail($email)) {
            throw new AccountAlreadyExistsException();
        }

        $registrationNumber = isset($data['registration_number']) ? trim((string) $data['registration_number']) : null;

        try {
            return $this->userRepository->create([
                'FK_TIPO_PERFIL' => $data['profile_type_id'] ?? null,
                'FK_TIPO_USUARIO' => null,
                'NO_USUARIO' => trim($data['name']),
                'NO_ORGAO_EMPRESA' => trim($data['organization']),
                'NO_SETOR' => trim($data['department']),
                'NU_MATRICULA' => $registrationNumber,
                'DS_EMAIL' => $email,
                'DS_SENHA' => Hash::make($data['password']),
                'ST_ATIVO' => self::STATUS_PENDING_ACTIVATION,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueEmailViolation($exception)) {
                throw new AccountAlreadyExistsException(previous: $exception);
            }

            throw $exception;
        }
    }

    private function isUniqueEmailViolation(QueryException $exception): bool
    {
        $code = (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($code, ['23000', '23505'], true)
            || (str_contains($message, 'unique') && str_contains($message, 'email'));
    }
}
