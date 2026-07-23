<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_COMPETENCIAS,
            'name' => $this->NO_COMPETENCIAS_PT,
            'description' => $this->DS_COMPETENCIAS_PT,
            'knowledge_area_id' => $this->FK_AREA_CONHECIMENTO,
            'difficulty_id' => $this->FK_DIFICULDADE,
            'type' => $this->TP_COMPETENCIAS,
        ];
    }
}
