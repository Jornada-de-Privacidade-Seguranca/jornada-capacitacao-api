<?php

namespace App\Http\Resources;

use App\Models\ProfileTypeChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfileTypeChange
 */
class AccessProfileHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_ALTERACAO_PERFIS,
            'previous_profile' => $this->previousProfileType !== null ? [
                'id' => $this->previousProfileType->PK_TIPO_PERFIL,
                'name' => $this->previousProfileType->NO_TIPO_PERFIL,
            ] : null,
            'new_profile' => $this->newProfileType !== null ? [
                'id' => $this->newProfileType->PK_TIPO_PERFIL,
                'name' => $this->newProfileType->NO_TIPO_PERFIL,
            ] : null,
            'changed_by' => $this->changedBy !== null ? [
                'id' => $this->changedBy->PK_USUARIO,
                'name' => $this->changedBy->NO_USUARIO,
            ] : null,
            'changed_at' => $this->DH_REGISTRO?->format('Y-m-d H:i:s'),
        ];
    }
}
