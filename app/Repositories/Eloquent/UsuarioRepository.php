<?php

namespace App\Repositories\Eloquent;

use App\Models\Usuario;
use App\Repositories\Contracts\UsuarioRepositoryInterface;

class UsuarioRepository implements UsuarioRepositoryInterface
{
    public function create(array $data): Usuario
    {
        return Usuario::query()->create($data);
    }

    public function existsByEmail(string $email): bool
    {
        return Usuario::query()
            ->where('DS_EMAIL', $email)
            ->exists();
    }
}
