<?php

namespace App\Repositories\Eloquent;

use App\Models\ProfileType;
use App\Models\ProfileTypeChange;
use App\Repositories\Contracts\AccessProfileRepositoryInterface;
use Illuminate\Support\Collection;

class AccessProfileRepository implements AccessProfileRepositoryInterface
{
    public function allWithPermissions(): Collection
    {
        return ProfileType::query()
            ->with([
                'permissions' => fn ($query) => $query->orderBy('TB_PERMISSOES.PK_PERMISSOES'),
            ])
            ->orderBy('PK_TIPO_PERFIL')
            ->get();
    }

    public function findOrFail(int $profileTypeId): ProfileType
    {
        return ProfileType::query()->findOrFail($profileTypeId);
    }

    public function recordProfileChange(array $data): ProfileTypeChange
    {
        return ProfileTypeChange::query()->create($data);
    }

    public function historyForUser(int $userId): Collection
    {
        return ProfileTypeChange::query()
            ->with(['previousProfileType', 'newProfileType', 'changedBy'])
            ->where('FK_USUARIO_ALTERADO', $userId)
            ->orderByDesc('DH_REGISTRO')
            ->orderByDesc('PK_ALTERACAO_PERFIS')
            ->get();
    }
}
