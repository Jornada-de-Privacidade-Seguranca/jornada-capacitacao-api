<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class JourneyFinalizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->PK_JORNADA,
            'status' => 'completed',
            'finished_at' => $this->DH_FIM?->format('Y-m-d H:i:s'),
        ];
    }
}
