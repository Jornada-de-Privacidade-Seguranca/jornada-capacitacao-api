<?php

namespace App\Repositories\Eloquent;

use App\Models\ProfessionalCertification;
use App\Repositories\Contracts\ProfessionalCertificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProfessionalCertificationRepository implements ProfessionalCertificationRepositoryInterface
{
    public function allWithKnowledgeDomain(): Collection
    {
        // TB_CERTIFICACAO_PROFISSIONAL ainda nao possui ST_ATIVO; quando existir, filtrar por ativo aqui sem alterar o contrato publico.
        return ProfessionalCertification::query()
            ->with('knowledgeDomain')
            ->orderBy('PK_CERTIFICACAO_PROFISSIONAL')
            ->get();
    }

    public function countExistingByIds(array $certificationIds): int
    {
        if ($certificationIds === []) {
            return 0;
        }

        return ProfessionalCertification::query()
            ->whereIn('PK_CERTIFICACAO_PROFISSIONAL', $certificationIds)
            ->count();
    }
}
