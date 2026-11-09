<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class AuthenticatedUserResource extends JsonResource
{
    private const ADMIN_PROFILE_TYPE = 1;

    private const MANAGER_PROFILE_TYPE = 2;

    public function toArray(Request $request): array
    {
        $role = $this->roleForProfile();
        $label = match ($role) {
            'administrator' => 'Administrador',
            'manager' => 'Gestor',
            default => 'Usuario comum',
        };

        return [
            'id' => $this->PK_USUARIO,
            'name' => $this->NO_USUARIO,
            'email' => $this->DS_EMAIL,
            'organization' => $this->NO_ORGAO_EMPRESA,
            'department' => $this->NO_SETOR,
            'registration_number' => $this->NU_MATRICULA,
            'created_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
            'role' => $role,
            'profile' => [
                'role' => $role,
                'label' => $label,
                'id' => $this->FK_TIPO_PERFIL,
                'name' => $this->profileType?->NO_TIPO_PERFIL,
                'permissions' => $this->permissionCodes()->all(),
                'profile_type_id' => $this->FK_TIPO_PERFIL,
                'profile_type_name' => $this->profileType?->NO_TIPO_PERFIL,
                'journey_profile_id' => $this->FK_PERFIL_JORNADA,
                'journey_profile_name' => $this->selectedJourneyProfile?->NO_PERFIS_PT,
                'journey_profile_name_en' => $this->selectedJourneyProfile?->NO_PERFIS_EN,
            ],
            'active_status' => $this->ST_ATIVO,
        ];
    }

    private function roleForProfile(): string
    {
        return match ((int) $this->FK_TIPO_PERFIL) {
            self::ADMIN_PROFILE_TYPE => 'administrator',
            self::MANAGER_PROFILE_TYPE => 'manager',
            default => 'common_user',
        };
    }
}
