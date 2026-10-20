<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyProfileRepository implements JourneyProfileRepositoryInterface
{
    public function all(int $page, int $perPage, ?string $modality = null): LengthAwarePaginator
    {
        $domainName = match ($modality) {
            'Privacidade' => 'Privacidade',
            'Segurança' => 'Segurança da Informação',
            'Privacidade/Segurança' => 'Conjunta (Privacidade/Segurança)',
            default => null,
        };

        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->when($domainName, fn ($query) => $query->whereHas(
                'knowledgeDomain',
                fn ($domainQuery) => $domainQuery->where('NO_DOMINIO_CONHECIMENTO_PT', $domainName),
            ))
            ->orderBy('PK_PERFIS_JORNADA')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function findOrFail(int $id): JourneyProfile
    {
        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->findOrFail($id);
    }
}
