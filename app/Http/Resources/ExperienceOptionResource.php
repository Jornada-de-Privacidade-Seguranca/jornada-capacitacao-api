<?php

namespace App\Http\Resources;

use App\Models\ProfessionalExperience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfessionalExperience
 */
class ExperienceOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $this->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $this->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }
}
