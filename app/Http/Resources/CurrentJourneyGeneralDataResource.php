<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class CurrentJourneyGeneralDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->PK_JORNADA,
            'status' => $this->DH_FIM === null ? 'ongoing' : 'completed',
            'is_complete' => $this->FK_SUBCRITERIOS_INGLES !== null
                && $this->FK_EXPERIENCIA_PROFISSIONAL !== null
                && $this->FK_ATUACAO_PERFIL !== null,
            'english_level' => $this->englishLevel(),
            'professional_experience' => $this->professionalExperience(),
            'profile_experience' => $this->profileExperience(),
        ];
    }

    private function englishLevel(): ?array
    {
        if ($this->englishSubcriterion === null) {
            return null;
        }

        return [
            'id' => $this->englishSubcriterion->PK_SUBCRITERIOS,
            'name_pt' => $this->englishSubcriterion->NO_SUBCRITERIO_PT,
            'name_en' => $this->englishSubcriterion->NO_SUBCRITERIO_EN,
        ];
    }

    private function professionalExperience(): ?array
    {
        if ($this->professionalExperience === null) {
            return null;
        }

        return [
            'id' => $this->professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $this->professionalExperience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $this->professionalExperience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }

    private function profileExperience(): ?array
    {
        if ($this->profileExperience === null) {
            return null;
        }

        return [
            'id' => $this->profileExperience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $this->profileExperience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $this->profileExperience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }
}
