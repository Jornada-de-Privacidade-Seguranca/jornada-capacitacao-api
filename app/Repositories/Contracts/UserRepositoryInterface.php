<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;

interface UserRepositoryInterface
{
    public function create(array $data): Usuario;

    public function existsByEmail(string $email): bool;

    public function findByEmail(string $email): ?Usuario;
}
