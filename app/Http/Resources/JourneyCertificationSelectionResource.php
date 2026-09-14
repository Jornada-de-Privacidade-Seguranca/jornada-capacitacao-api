<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class JourneyCertificationSelectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->PK_JORNADA,
            'status' => 'ongoing',
            'is_complete' => $this->isComplete(),
            'certifications' => CertificationOptionResource::collection($this->professionalCertifications)->resolve(),
        ];
    }

    private function isComplete(): bool
    {
        $isRequiredStep = false;

        return ! $isRequiredStep || $this->professionalCertifications->isNotEmpty();
    }
}
