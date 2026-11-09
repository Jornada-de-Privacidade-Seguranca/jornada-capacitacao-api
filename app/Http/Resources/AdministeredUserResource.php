<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class AdministeredUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'active_status' => (int) $this->ST_ATIVO,
        ];
    }
}
