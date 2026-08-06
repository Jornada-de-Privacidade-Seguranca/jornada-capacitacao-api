<?php

namespace App\Repositories\Eloquent;

use App\Models\Journey;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyRepository implements JourneyRepositoryInterface
{
    public function paginatedByUser(int $userId, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Journey::query()
            ->with([
                'usuario',
                'journeyProfile.knowledgeDomain',
            ])
            ->withCount([
                'acquiredKnowledges',
                'professionalCertifications',
            ])
            ->where('FK_USUARIO', $userId);

        if (($filters['status'] ?? null) === 'completed') {
            $query->whereNotNull('DH_FIM');
        }

        if (($filters['status'] ?? null) === 'ongoing') {
            $query->whereNull('DH_FIM');
        }

        if (isset($filters['journey_profile_id'])) {
            $query->where('FK_PERFIS_JORNADA', $filters['journey_profile_id']);
        }

        if (isset($filters['started_from'])) {
            $query->whereDate('DH_CRIADO_EM', '>=', $filters['started_from']);
        }

        if (isset($filters['started_to'])) {
            $query->whereDate('DH_CRIADO_EM', '<=', $filters['started_to']);
        }

        if (isset($filters['ended_from'])) {
            $query->whereDate('DH_FIM', '>=', $filters['ended_from']);
        }

        if (isset($filters['ended_to'])) {
            $query->whereDate('DH_FIM', '<=', $filters['ended_to']);
        }

        return $query
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
