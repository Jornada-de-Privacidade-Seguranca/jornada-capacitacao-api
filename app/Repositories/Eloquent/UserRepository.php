<?php

namespace App\Repositories\Eloquent;

use App\Models\ProfileType;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\Concerns\AppliesNormalizedSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserRepository implements UserRepositoryInterface
{
    use AppliesNormalizedSearch;

    public function create(array $data): Usuario
    {
        return Usuario::query()->create($data);
    }

    public function existsByEmail(string $email): bool
    {
        return Usuario::withTrashed()
            ->whereRaw('LOWER("DS_EMAIL") = ?', [strtolower($email)])
            ->exists();
    }

    public function existsByEmailExcept(string $email, int $exceptUserId): bool
    {
        return Usuario::withTrashed()
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
            ->orderBy('DH_CRIADO_EM')
            ->orderBy('PK_USUARIO')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginatedUsers(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Usuario::query()
            ->with(['profileType']);

        if (isset($filters['profile_type_id'])) {
            $query->where('FK_TIPO_PERFIL', (int) $filters['profile_type_id']);
        }

        if (! empty($filters['department'])) {
            $query->where('NO_SETOR', trim((string) $filters['department']));
        }

        if (isset($filters['status'])) {
            $statusCode = match ($filters['status']) {
                'active' => 1,
                'pending_activation' => 2,
                'inactive' => 0,
                default => null,
            };

            if ($statusCode !== null) {
                $query->where('ST_ATIVO', $statusCode);
            }
        }

        if (! empty($filters['search'])) {
            $this->applyUserSearch($query, trim((string) $filters['search']));
        }

        return $query
            ->orderBy('NO_USUARIO')
            ->orderBy('PK_USUARIO')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function lockActiveAdministrators(): void
    {
        Usuario::query()
            ->where('FK_TIPO_PERFIL', ProfileType::ADMIN_ID)
            ->where('ST_ATIVO', 1)
            ->lockForUpdate()
            ->get(['PK_USUARIO']);
    }

    public function countActiveAdministratorsExcept(int $userId): int
    {
        return Usuario::query()
            ->where('FK_TIPO_PERFIL', ProfileType::ADMIN_ID)
            ->where('ST_ATIVO', 1)
            ->whereKeyNot($userId)
            ->count();
    }

    public function softDelete(Usuario $user): void
    {
        $user->ST_ATIVO = 0;
        $user->save();
        $user->delete();
    }

    private function applyUserSearch(Builder $query, string $search): void
    {
        $escapedSearchTerm = $this->escapeSearchWildcards($this->normalizeSearchTerm($search));
        $pattern = '%'.$escapedSearchTerm.'%';
        $columns = ['NO_USUARIO', 'DS_EMAIL', 'NU_MATRICULA'];
        $connection = $query->getConnection();
        $grammar = $query->getQuery()->getGrammar();

        $query->where(function (Builder $subQuery) use ($columns, $pattern, $connection, $grammar): void {
            foreach ($columns as $column) {
                $wrappedColumn = $grammar->wrap($column);
                $normalizedColumn = $this->normalizedSearchExpression($connection, $wrappedColumn);
                $subQuery->orWhereRaw("{$normalizedColumn} LIKE ? ESCAPE '\\'", [$pattern]);
            }
        });
    }
}
