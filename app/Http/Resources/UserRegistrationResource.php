<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class UserRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'organization_company' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'profile_type_id' => $this->FK_TIPO_PERFIL,
            'active_status' => $this->ST_ATIVO,
            'account_status' => 'pending_activation',
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
        ];
    }
}
