<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Formate aqui somente os campos da resposta.
        // Nao coloque success, message, errors ou meta em Resources.
        return [
            'route_name' => $this->resource['route_name'],
            'description' => $this->resource['description'],
            'request_context' => $this->resource['request_context'],
            'layers' => $this->resource['layers'],
            'repository_note' => $this->resource['repository_note'],
        ];
    }
}
