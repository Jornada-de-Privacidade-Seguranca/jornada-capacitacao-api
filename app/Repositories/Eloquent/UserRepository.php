<?php

namespace App\Repositories\Eloquent;

use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function existsByEmailExcept(string $email, int $exceptUserId): bool
    {
        return Usuario::query()
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->whereKeyNot($exceptUserId)
            ->exists();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return Usuario::query()
            ->with(['profileType', 'selectedJourneyProfile'])
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->first();
    }

    public function findByEmailForUpdate(string $email): ?Usuario
    {
        return Usuario::query()
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->lockForUpdate()
            ->first();
    }

    public function findById(int $id): ?Usuario
    {
        return Usuario::query()
            ->with(['profileType', 'selectedJourneyProfile'])
            ->find($id);
    }

    public function lockForUpdate(int $id): void
    {
        Usuario::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function update(Usuario $user, array $data): Usuario
    {
        $user->fill($data);
        $user->save();

        return $user->refresh()->load(['profileType', 'selectedJourneyProfile']);
    }

    public function findByIdForUpdate(int $id): ?Usuario
    {
        return Usuario::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function pendingActivationPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return Usuario::query()
            ->where('ST_ATIVO', 2)
            ->whereNull('FK_TIPO_USUARIO')
            ->orderBy('DH_CRIADO_EM')
            ->orderBy('PK_USUARIO')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
