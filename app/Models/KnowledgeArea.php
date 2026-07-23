<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArea extends JornadaModel
{
    protected $table = 'TB_AREA_CONHECIMENTO';

    protected $primaryKey = 'PK_AREA_CONHECIMENTO';

    protected $fillable = [
        'NO_AREA_CONHECIMENTO_PT',
        'NO_AREA_CONHECIMENTO_EN',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class, 'FK_AREA_CONHECIMENTO', 'PK_AREA_CONHECIMENTO');
    }
}
