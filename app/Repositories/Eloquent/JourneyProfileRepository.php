<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            ->select('TB_PERFIS_JORNADA.*')
            ->selectSub(
                DB::table('RL_PERFIS_COMPETENCIAS')
                    ->whereColumn('RL_PERFIS_COMPETENCIAS.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
                    ->selectRaw('COUNT(DISTINCT RL_PERFIS_COMPETENCIAS.FK_COMPETENCIAS)'),
                'competencies_count',
            )
            ->selectSub(
                DB::table('RL_PERFIS_COMPETENCIAS')
                    ->join('TB_COMPETENCIAS', 'TB_COMPETENCIAS.PK_COMPETENCIAS', '=', 'RL_PERFIS_COMPETENCIAS.FK_COMPETENCIAS')
                    ->whereColumn('RL_PERFIS_COMPETENCIAS.FK_PERFIS_JORNADA', 'TB_PERFIS_JORNADA.PK_PERFIS_JORNADA')
                    ->selectRaw('COUNT(DISTINCT TB_COMPETENCIAS.FK_AREA_CONHECIMENTO)'),
                'knowledge_areas_count',
            )
            ->findOrFail($id);
    }
}
