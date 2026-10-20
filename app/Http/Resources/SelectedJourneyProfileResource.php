<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class SelectedJourneyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_profile_id' => $this->FK_PERFIL_JORNADA,
            'journey_profile' => (new JourneyProfileResource($this->selectedJourneyProfile))->resolve(),
        ];
    }
}
