<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActivationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'profile_type_id' => $this->FK_TIPO_PERFIL,
            'profile_type_name' => $this->profileType?->NO_TIPO_PERFIL,
            'active_status' => $this->ST_ATIVO,
            'account_status' => 'active',
        ];
    }
}
