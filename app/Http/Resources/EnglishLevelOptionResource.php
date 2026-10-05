<?php

namespace App\Http\Resources;

use App\Models\Subcriterion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subcriterion
 */
class EnglishLevelOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_SUBCRITERIOS,
            'name_pt' => $this->NO_SUBCRITERIO_PT,
            'name_en' => $this->NO_SUBCRITERIO_EN,
        ];
    }
}
