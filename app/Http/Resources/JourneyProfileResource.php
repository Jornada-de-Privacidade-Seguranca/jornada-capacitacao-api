<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_PERFIS_JORNADA,
            'name_pt' => $this->NO_PERFIS_PT,
            'name_en' => $this->NO_PERFIS_EN,
            'description_pt' => $this->DS_PERFIS_PT,
            'description_en' => $this->DS_PERFIS_EN,
            'type' => $this->FK_DOMINIO_CONHECIMENTO,
        ];
    }
}
