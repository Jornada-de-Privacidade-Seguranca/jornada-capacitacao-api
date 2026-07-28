<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'profile_type_id' => $this->FK_TIPO_PERFIL,
            'name' => $this->NO_USUARIO,
            'organization' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'email' => $this->DS_EMAIL,
            'active' => (bool) $this->ST_ATIVO,
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
        ];
    }
}
