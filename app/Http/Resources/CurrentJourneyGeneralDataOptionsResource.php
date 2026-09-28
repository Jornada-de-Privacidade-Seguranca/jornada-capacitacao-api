<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentJourneyGeneralDataOptionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $experienceOptions = ExperienceOptionResource::collection($this->resource['experience_options'])
            ->resolve($request);

        return [
            'journey_id' => $this->resource['journey']->PK_JORNADA,
            'status' => 'ongoing',
            'fields' => [
                'english_level' => [
                    'required' => true,
                    'options' => EnglishLevelOptionResource::collection($this->resource['english_levels'])
                        ->resolve($request),
                ],
                'professional_experience' => [
                    'required' => true,
                    'options' => $experienceOptions,
                ],
                'profile_experience' => [
                    'required' => true,
                    'options' => $experienceOptions,
                ],
            ],
        ];
    }
}
