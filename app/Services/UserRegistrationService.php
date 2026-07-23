<?php

namespace App\Services;

use App\Exceptions\AccountAlreadyExistsException;
use App\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService
{
    public function __construct(private readonly UsuarioRepositoryInterface $usuarioRepository)
    {
    }

    public function register(array $data): Usuario
    {
        if ($this->usuarioRepository->existsByEmail($data['email'])) {
            throw new AccountAlreadyExistsException();
        }

        try {
            return $this->usuarioRepository->create([
                'FK_TIPO_PERFIL' => $data['profile_type_id'] ?? null,
                'FK_TIPO_USUARIO' => $data['user_type_id'] ?? null,
                'NO_USUARIO' => $data['name'],
                'NO_ORGAO_EMPRESA' => $data['organization'],
                'NO_SETOR' => $data['department'],
                'NU_MATRICULA' => $data['registration_number'],
                'DS_EMAIL' => $data['email'],
                'DS_SENHA' => Hash::make($data['password']),
                'ST_ATIVO' => 0,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new AccountAlreadyExistsException(previous: $exception);
            }

            throw $exception;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
