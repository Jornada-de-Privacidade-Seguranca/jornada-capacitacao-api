<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class SystemSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_name' => $this->resource['app_name'],
            'environment' => $this->resource['environment'],
            'tag' => $this->resource['tag'],
            'database' => $this->resource['database'],
        ];
    }
}
