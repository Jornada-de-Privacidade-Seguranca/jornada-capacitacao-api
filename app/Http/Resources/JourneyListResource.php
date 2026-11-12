<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsAssignedBy;
use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 *
 * @property-read int|null $acquired_knowledges_count
 * @property-read int|null $professional_certifications_count
 */
class JourneyListResource extends JsonResource
{
    use FormatsAssignedBy;

    public function toArray(Request $request): array
    {
        $isCompleted = $this->DH_FIM !== null;
        $isCancelled = $this->DH_CANCELADA_EM !== null;

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
            'cancelled_at' => $this->DH_CANCELADA_EM?->format('Y-m-d H:i:s'),
            'origin' => $this->origin,
            'assigned_by' => $this->assignedBySummary(),
            'status' => $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : 'ongoing'),
            'is_completed' => $isCompleted,
            'is_cancelled' => $isCancelled,
            'score' => $this->NU_PONTUACAO_TOTAL,
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
