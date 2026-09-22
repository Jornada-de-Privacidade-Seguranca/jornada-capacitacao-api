<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;

interface UserRepositoryInterface
{
    public function create(array $data): Usuario;

    public function existsByEmail(string $email): bool;

    public function findByEmail(string $email): ?Usuario;

    public function findById(int $id): ?Usuario;

    public function lockForUpdate(int $id): void;

    public function update(Usuario $user, array $data): Usuario;
}
