<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class AdminResultMetricsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_completed_journeys' => $this->resource['total_completed_journeys'],
            'distinct_users_count' => $this->resource['distinct_users_count'],
            'domains' => $this->resource['domains'],
        ];
    }
}
