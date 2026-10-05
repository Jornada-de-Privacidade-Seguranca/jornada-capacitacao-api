<?php

namespace App\Http\Resources;

use App\Models\AcquiredKnowledge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcquiredKnowledge
 */
class CurrentJourneyCompetencyAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_CONHECIMENTOS_ADQUIRIDOS,
            'acquired' => (int) $this->TP_ADQUIRIDO === 1,
            'type_acquired' => $this->TP_ADQUIRIDO,
            'competency' => (new CurrentJourneyCompetencyOptionResource($this->competency))->resolve(),
        ];
    }
}
