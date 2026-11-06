<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class DashboardUserResource extends JsonResource
{
    private const ADMIN_PROFILE_TYPE = 1;

    private const MANAGER_PROFILE_TYPE = 2;

    public function toArray(Request $request): array
    {
        $role = match ((int) $this->FK_TIPO_PERFIL) {
            self::ADMIN_PROFILE_TYPE => 'administrator',
            self::MANAGER_PROFILE_TYPE => 'manager',
            default => 'common_user',
        };
        $label = match ($role) {
            'administrator' => 'Administrador',
            'manager' => 'Gestor',
            default => 'Usuario comum',
        };

        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'role' => $role,
            'organization' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'profile' => [
                'role' => $role,
                'label' => $label,
                'id' => $this->FK_TIPO_PERFIL,
                'name' => $this->profileType?->NO_TIPO_PERFIL,
                'permissions' => $this->permissionCodes()->all(),
                'profile_type_id' => $this->FK_TIPO_PERFIL,
                'profile_type_name' => $this->profileType?->NO_TIPO_PERFIL,
            ],
            'active_status' => $this->ST_ATIVO,
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
        ];
    }
}
