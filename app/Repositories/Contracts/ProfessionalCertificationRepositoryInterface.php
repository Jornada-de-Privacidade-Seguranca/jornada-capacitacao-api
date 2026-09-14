<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ProfessionalCertificationRepositoryInterface
{
    public function allWithKnowledgeDomain(): Collection;

    /**
     * @param  array<int, int>  $certificationIds
     */
    public function countExistingByIds(array $certificationIds): int;
}
