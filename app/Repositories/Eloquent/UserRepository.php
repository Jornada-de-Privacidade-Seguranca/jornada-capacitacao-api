<?php

namespace App\Repositories\Eloquent;

use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): Usuario
    {
        return Usuario::query()->create($data);
    }

    public function existsByEmail(string $email): bool
    {
        return Usuario::query()
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->exists();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return Usuario::query()
            ->with('profileType')
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->first();
    }
}
