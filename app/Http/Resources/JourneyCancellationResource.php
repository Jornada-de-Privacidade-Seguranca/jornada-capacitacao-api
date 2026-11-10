<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class JourneyCancellationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->PK_JORNADA,
            'status' => 'cancelled',
            'cancelled_at' => $this->DH_CANCELADA_EM?->format('Y-m-d H:i:s'),
            'is_completed' => false,
        ];
    }
}
