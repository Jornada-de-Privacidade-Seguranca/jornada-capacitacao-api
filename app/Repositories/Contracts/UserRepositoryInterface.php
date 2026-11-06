<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(array $data): Usuario;

    public function existsByEmail(string $email): bool;

    public function existsByEmailExcept(string $email, int $exceptUserId): bool;

    public function findByEmail(string $email): ?Usuario;

    public function findById(int $id): ?Usuario;

    public function lockForUpdate(int $id): void;

    public function update(Usuario $user, array $data): Usuario;

    public function findByIdForUpdate(int $id): ?Usuario;

    public function pendingActivationPaginated(int $page, int $perPage): LengthAwarePaginator;
}
