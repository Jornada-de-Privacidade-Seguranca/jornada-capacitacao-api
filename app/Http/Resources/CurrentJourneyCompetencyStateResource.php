<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentJourneyCompetencyStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->resource['journey_id'],
            'status' => $this->resource['status'],
            'is_complete' => $this->resource['is_complete'],
            'competencies' => CurrentJourneyCompetencyAnswerResource::collection($this->resource['competencies'])->resolve(),
        ];
    }
}
