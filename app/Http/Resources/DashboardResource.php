<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => (new DashboardUserResource($this->resource['user']))->resolve(),
            'journeys' => [
                'has_unfinished_journey' => $this->resource['has_unfinished_journey'],
                'unfinished_journey' => $this->resource['unfinished_journey'] === null
                    ? null
                    : (new DashboardJourneyResource($this->resource['unfinished_journey']))->resolve(),
                'latest_journey' => $this->resource['latest_journey'] === null
                    ? null
                    : (new DashboardJourneyResource($this->resource['latest_journey']))->resolve(),
                'recent_journeys' => DashboardJourneyResource::collection(
                    $this->resource['recent_journeys']
                )->resolve(),
            ],
        ];
    }
}
