<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;

interface UsuarioRepositoryInterface
{
    public function create(array $data): Usuario;

    public function existsByEmail(string $email): bool;
}
