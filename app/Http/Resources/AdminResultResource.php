<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class AdminResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_JORNADA,
            'user' => [
                'id' => $this->usuario?->PK_USUARIO,
                'name' => $this->usuario?->NO_USUARIO,
                'email' => $this->usuario?->DS_EMAIL,
                'department' => $this->usuario?->NO_SETOR,
            ],
            'journey_profile' => [
                'id' => $this->journeyProfile?->PK_PERFIS_JORNADA,
                'name_pt' => $this->journeyProfile?->NO_PERFIS_PT,
                'name_en' => $this->journeyProfile?->NO_PERFIS_EN,
            ],
            'knowledge_domain' => [
                'id' => $this->journeyProfile?->knowledgeDomain?->PK_DOMINIO_CONHECIMENTO,
                'name_pt' => $this->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_PT,
                'name_en' => $this->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_EN,
            ],
            'finished_at' => $this->DH_FIM?->format('Y-m-d H:i:s'),
            'score' => $this->NU_PONTUACAO_TOTAL,
        ];
    }
}
