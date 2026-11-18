<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class UserManagementListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'organization' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'access_profile' => $this->profileType !== null ? [
                'id' => $this->profileType->PK_TIPO_PERFIL,
                'name' => $this->profileType->NO_TIPO_PERFIL,
            ] : null,
            'status' => $this->statusLabel(),
            'active_status' => (int) $this->ST_ATIVO,
            'completed_journeys_count' => $this->whenCounted('completedJourneys', fn ($count): int => (int) $count),
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
        ];
    }

    protected function statusLabel(): string
    {
        return match ((int) $this->ST_ATIVO) {
            1 => 'active',
            2 => 'pending_activation',
            default => 'inactive',
        };
    }
}
