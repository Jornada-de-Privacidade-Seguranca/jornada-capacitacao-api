<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HigherEducationCourse extends JornadaModel
{
    protected $table = 'TB_CURSO_SUPERIOR';

    protected $primaryKey = 'PK_CURSO_SUPERIOR';

    protected $fillable = [
        'NO_CURSO_SUPERIOR_PT',
        'NO_CURSO_SUPERIOR_EN',
        'TP_CURSO_SUPERIOR',
    ];

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_CURSO_SUPERIOR', 'PK_CURSO_SUPERIOR');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Subcriterio::class, 'TP_CURSO_SUPERIOR', 'PK_SUBCRITERIOS');
    }
}
