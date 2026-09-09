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
            'english_level' => $this->englishLevel(),
            'professional_experience' => $this->experienceOption($this->professionalExperience),
            'profile_experience' => $this->experienceOption($this->profileExperience),
            'is_complete' => $this->isComplete(),
            'status' => $this->DH_FIM === null ? 'ongoing' : 'completed',
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

    private function experienceOption(mixed $experience): ?array
    {
        if ($experience === null) {
            return null;
        }

        return [
            'id' => $experience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $experience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $experience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }

    private function isComplete(): bool
    {
        return $this->englishSubcriterion !== null
            && $this->professionalExperience !== null
            && $this->profileExperience !== null;
    }
}
