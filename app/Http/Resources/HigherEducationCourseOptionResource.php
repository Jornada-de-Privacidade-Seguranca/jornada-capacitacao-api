<?php

namespace App\Http\Resources;

use App\Models\HigherEducationCourse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HigherEducationCourse
 */
class HigherEducationCourseOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_CURSO_SUPERIOR,
            'name_pt' => $this->NO_CURSO_SUPERIOR_PT,
            'name_en' => $this->NO_CURSO_SUPERIOR_EN,
            'type' => $this->subcriterion === null ? null : [
                'id' => $this->subcriterion->PK_SUBCRITERIOS,
                'name_pt' => $this->subcriterion->NO_SUBCRITERIO_PT,
                'name_en' => $this->subcriterion->NO_SUBCRITERIO_EN,
            ],
        ];
    }
}
