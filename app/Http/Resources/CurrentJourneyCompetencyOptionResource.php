<?php

namespace App\Http\Resources;

use App\Models\Competency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Competency
 */
class CurrentJourneyCompetencyOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_COMPETENCIAS,
            'origin_pt' => $this->NO_ORIGEM_COMPETENCIAS_PT,
            'origin_en' => $this->NO_ORIGEM_COMPETENCIAS_EN,
            'name_pt' => $this->NO_COMPETENCIAS_PT,
            'name_en' => $this->NO_COMPETENCIAS_EN,
            'description_pt' => $this->DS_COMPETENCIAS_PT,
            'description_en' => $this->DS_COMPETENCIAS_EN,
            'knowledge_domain' => $this->knowledgeDomain(),
            'knowledge_area' => $this->knowledgeArea(),
            'difficulty' => $this->difficulty(),
        ];
    }

    private function knowledgeDomain(): ?array
    {
        if ($this->knowledgeDomain === null) {
            return null;
        }

        return [
            'id' => $this->knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'name_pt' => $this->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_PT,
            'name_en' => $this->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_EN,
        ];
    }

    private function knowledgeArea(): ?array
    {
        if ($this->knowledgeArea === null) {
            return null;
        }

        return [
            'id' => $this->knowledgeArea->PK_AREA_CONHECIMENTO,
            'name_pt' => $this->knowledgeArea->NO_AREA_CONHECIMENTO_PT,
            'name_en' => $this->knowledgeArea->NO_AREA_CONHECIMENTO_EN,
        ];
    }

    private function difficulty(): ?array
    {
        if ($this->difficulty === null) {
            return null;
        }

        return [
            'id' => $this->difficulty->PK_DIFICULDADE,
            'name_pt' => $this->difficulty->NO_DIFICULDADE_PT,
            'name_en' => $this->difficulty->NO_DIFICULDADE_EN,
        ];
    }
}
