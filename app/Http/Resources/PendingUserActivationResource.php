<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class PendingUserActivationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'organization' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
        ];
    }
}
