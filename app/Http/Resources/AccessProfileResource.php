<?php

namespace App\Http\Resources;

use App\Models\Permission;
use App\Models\ProfileType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfileType
 */
class AccessProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_TIPO_PERFIL,
            'name' => $this->NO_TIPO_PERFIL,
            'permissions' => $this->permissions
                ->map(fn (Permission $permission): array => [
                    'id' => $permission->PK_PERMISSOES,
                    'code' => $permission->NO_PERMISSOES,
                    'description_pt' => $permission->DS_PERMISSOES_PT,
                    'description_en' => $permission->DS_PERMISSOES_EN,
                ])
                ->values()
                ->all(),
        ];
    }
}
