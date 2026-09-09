<?php

namespace App\Http\Resources;

use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyGeneralDataOptionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->resource['journey']->PK_JORNADA,
            'english_levels' => $this->resource['english_levels']
                ->map(fn (Subcriterion $subcriterion): array => $this->englishLevel($subcriterion))
                ->values()
                ->all(),
            'professional_experiences' => $this->resource['professional_experiences']
                ->map(fn (ProfessionalExperience $experience): array => $this->experience($experience))
                ->values()
                ->all(),
            'profile_experiences' => $this->resource['profile_experiences']
                ->map(fn (ProfessionalExperience $experience): array => $this->experience($experience))
                ->values()
                ->all(),
            'required_fields' => [
                'english_level' => true,
                'professional_experience' => true,
                'profile_experience' => true,
            ],
        ];
    }

    private function englishLevel(Subcriterion $subcriterion): array
    {
        return [
            'id' => $subcriterion->PK_SUBCRITERIOS,
            'name_pt' => $subcriterion->NO_SUBCRITERIO_PT,
            'name_en' => $subcriterion->NO_SUBCRITERIO_EN,
        ];
    }

    private function experience(ProfessionalExperience $experience): array
    {
        return [
            'id' => $experience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $experience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $experience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }
}
