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
            'status' => 'ongoing',
            'is_complete' => $this->FK_SUBCRITERIOS_INGLES !== null
                && $this->FK_EXPERIENCIA_PROFISSIONAL !== null
                && $this->FK_ATUACAO_PERFIL !== null,
            'english_level' => $this->englishSubcriterion === null
                ? null
                : (new EnglishLevelOptionResource($this->englishSubcriterion))->resolve($request),
            'professional_experience' => $this->professionalExperience === null
                ? null
                : (new ExperienceOptionResource($this->professionalExperience))->resolve($request),
            'profile_experience' => $this->profileExperience === null
                ? null
                : (new ExperienceOptionResource($this->profileExperience))->resolve($request),
        ];
    }
}
