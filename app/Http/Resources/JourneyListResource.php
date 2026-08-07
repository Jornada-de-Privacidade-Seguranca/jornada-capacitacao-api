<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isCompleted = $this->DH_FIM !== null;

        return [
            'id' => $this->PK_JORNADA,
            'user_id' => $this->FK_USUARIO,
            'user_name' => $this->usuario?->NO_USUARIO,
            'journey_profile' => [
                'id' => $this->journeyProfile?->PK_PERFIS_JORNADA,
                'name' => $this->journeyProfile?->NO_PERFIS_PT,
                'description' => $this->journeyProfile?->DS_PERFIS_PT,
                'knowledge_domain' => [
                    'id' => $this->journeyProfile?->knowledgeDomain?->PK_DOMINIO_CONHECIMENTO,
                    'name' => $this->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_PT,
                ],
            ],
            'started_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
            'finished_at' => $this->DH_FIM?->format('Y-m-d H:i:s'),
            'status' => $isCompleted ? 'completed' : 'ongoing',
            'is_completed' => $isCompleted,
            'summary' => [
                'acquired_knowledges_count' => $this->acquired_knowledges_count ?? 0,
                'professional_certifications_count' => $this->professional_certifications_count ?? 0,
            ],
            'detail' => [
                'journey_id' => $this->PK_JORNADA,
                'user_id' => $this->FK_USUARIO,
            ],
        ];
    }
}
