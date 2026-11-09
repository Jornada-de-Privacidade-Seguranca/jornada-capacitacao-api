<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class InvitedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'email' => $this->DS_EMAIL,
            'user_type_id' => $this->FK_TIPO_USUARIO,
            'active_status' => $this->ST_ATIVO,
            'account_status' => (int) $this->ST_ATIVO === 1 ? 'active' : 'pending_initial_password',
        ];
    }
}
