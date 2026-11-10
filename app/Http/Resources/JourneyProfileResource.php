<?php

namespace App\Http\Resources;

use App\Models\JourneyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JourneyProfile
 */
class JourneyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_PERFIS_JORNADA,
            'name_pt' => $this->NO_PERFIS_PT,
            'name_en' => $this->NO_PERFIS_EN,
            'description_pt' => $this->DS_PERFIS_PT,
            'description_en' => $this->DS_PERFIS_EN,
            'type' => $this->FK_DOMINIO_CONHECIMENTO,
            'knowledge_domain' => $this->knowledgeDomain(),
            'user_status' => $this->when(
                array_key_exists('completed_journey_exists', $this->resource->getAttributes()),
                $this->userStatus(),
            ),
            'last_score' => $this->when(
                array_key_exists('last_score', $this->resource->getAttributes()),
                $this->lastScore(),
            ),
            'competencies_count' => $this->when(array_key_exists('competencies_count', $this->resource->getAttributes()), (int) $this->resource->getAttribute('competencies_count')),
            'knowledge_areas_count' => $this->when(
                array_key_exists('knowledge_areas_count', $this->resource->getAttributes()),
                (int) $this->resource->getAttribute('knowledge_areas_count'),
            ),
        ];
    }

    private function userStatus(): string
    {
        if ($this->resource->getAttribute('pending_journey_exists') !== null) {
            return 'pending';
        }

        return $this->resource->getAttribute('completed_journey_exists') !== null ? 'completed' : 'not_done';
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

    private function lastScore(): ?float
    {
        $score = $this->resource->getAttribute('last_score');

        return $score === null ? null : (float) $score;
    }
}
