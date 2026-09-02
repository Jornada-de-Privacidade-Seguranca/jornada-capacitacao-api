<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HigherEducationCourseOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_CURSO_SUPERIOR,
            'name_pt' => $this->NO_CURSO_SUPERIOR_PT,
            'name_en' => $this->NO_CURSO_SUPERIOR_EN,
            'type' => $this->type === null ? null : [
                'id' => $this->type->PK_SUBCRITERIOS,
                'name_pt' => $this->type->NO_SUBCRITERIOS_PT,
                'name_en' => $this->type->NO_SUBCRITERIOS_EN,
            ],
        ];
    }
}
