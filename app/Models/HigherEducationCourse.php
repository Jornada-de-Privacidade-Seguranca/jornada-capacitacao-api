<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HigherEducationCourse extends JornadaModel
{
    protected $table = 'TB_CURSO_SUPERIOR';

    protected $primaryKey = 'PK_CURSO_SUPERIOR';

    protected $fillable = [
        'FK_SUBCRITERIOS',
        'NO_CURSO_SUPERIOR_PT',
        'NO_CURSO_SUPERIOR_EN',
    ];

    public function subcriterion(): BelongsTo
    {
        return $this->belongsTo(Subcriterion::class, 'FK_SUBCRITERIOS', 'PK_SUBCRITERIOS');
    }

    public function journeys(): BelongsToMany
    {
        return $this->belongsToMany(
            Journey::class,
            'RL_CURSOS_SUPERIORES_JORNADA',
            'FK_CURSO_SUPERIOR',
            'FK_JORNADA',
            'PK_CURSO_SUPERIOR',
            'PK_JORNADA',
        )->withPivot('PK_CURSOS_SUPERIORES_JORNADA');
    }
}
