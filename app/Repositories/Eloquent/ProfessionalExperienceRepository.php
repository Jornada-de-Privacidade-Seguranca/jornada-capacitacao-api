<?php

namespace App\Repositories\Eloquent;

use App\Models\ProfessionalExperience;
use App\Repositories\Contracts\ProfessionalExperienceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProfessionalExperienceRepository implements ProfessionalExperienceRepositoryInterface
{
    public function allOrdered(): Collection
    {
        return ProfessionalExperience::query()
            ->orderBy('PK_EXPERIENCIA_ATUACAO')
            ->get();
    }
}
