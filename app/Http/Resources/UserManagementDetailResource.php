<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserManagementDetailResource extends UserManagementListResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'journey_profile_id' => $this->FK_PERFIL_JORNADA !== null ? (int) $this->FK_PERFIL_JORNADA : null,
        ]);
    }
}
