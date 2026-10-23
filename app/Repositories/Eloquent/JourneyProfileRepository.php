<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyProfileRepository implements JourneyProfileRepositoryInterface
{
    private const PRIVACY_DOMAIN_ID = 1;

    private const SECURITY_DOMAIN_ID = 2;

    private const JOINT_DOMAIN_ID = 3;

    public function all(int $page, int $perPage, ?string $modality = null): LengthAwarePaginator
    {
        $domainIds = match ($modality) {
            'Privacidade' => [self::PRIVACY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            'Segurança' => [self::SECURITY_DOMAIN_ID, self::JOINT_DOMAIN_ID],
            default => null,
        };

        return JourneyProfile::query()
            ->whereNotNull('NO_PERFIS_PT')
            ->when($domainIds !== null, fn ($query) => $query->whereIn('FK_DOMINIO_CONHECIMENTO', $domainIds))
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
