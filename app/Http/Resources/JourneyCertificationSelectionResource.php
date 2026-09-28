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
            // A etapa de certificacoes profissionais e opcional.
            'is_complete' => true,
            'certifications' => CertificationOptionResource::collection($this->professionalCertifications)->resolve(),
        ];
    }
}
