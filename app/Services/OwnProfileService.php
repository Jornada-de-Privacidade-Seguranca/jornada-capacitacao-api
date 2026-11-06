<?php

namespace App\Services;

use App\Exceptions\EmailAlreadyInUseException;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\QueryException;

class OwnProfileService
{
    /** Campo da requisicao => coluna de TB_USUARIO. */
    private const COLUMN_BY_FIELD = [
        'name' => 'NO_USUARIO',
        'email' => 'DS_EMAIL',
        'organization' => 'NO_ORGAO_EMPRESA',
        'department' => 'NO_SETOR',
    ];

    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    /**
     * Atualiza so os campos enviados. O e-mail precisa continuar unico,
     * sem diferenciar maiusculas e minusculas.
     *
     * @param  array<string, string>  $data
     */
    public function update(Usuario $user, array $data): Usuario
    {
        $attributes = [];
        foreach (self::COLUMN_BY_FIELD as $field => $column) {
            if (array_key_exists($field, $data)) {
                $attributes[$column] = $data[$field];
            }
        }

        $email = $attributes['DS_EMAIL'] ?? null;
        if ($email !== null && $this->userRepository->existsByEmailExcept($email, (int) $user->PK_USUARIO)) {
            throw new EmailAlreadyInUseException;
        }

        try {
            return $this->userRepository->update($user, $attributes);
        } catch (QueryException $exception) {
            if ($email !== null && $this->isUniqueEmailViolation($exception)) {
                throw new EmailAlreadyInUseException(previous: $exception);
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
