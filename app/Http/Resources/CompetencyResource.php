<?php

namespace App\Http\Resources;

use App\Models\Competency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Competency
 */
class CompetencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_COMPETENCIAS,
            'name' => $this->NO_COMPETENCIAS_PT,
            'description' => $this->DS_COMPETENCIAS_PT,
            'knowledge_domain_id' => $this->FK_DOMINIO_CONHECIMENTO,
            'knowledge_area_id' => $this->FK_AREA_CONHECIMENTO,
            'difficulty_id' => $this->FK_DIFICULDADE,
        ];
    }
}
