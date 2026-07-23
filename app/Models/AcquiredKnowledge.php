<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquiredKnowledge extends JornadaModel
{
    protected $table = 'TB_CONHECIMENTOS_ADQUIRIDOS';

    protected $primaryKey = 'PK_CONHECIMENTOS_ADQUIRIDOS';

    protected $fillable = [
        'FK_JORNADA',
        'FK_COMPETENCIAS',
        'TP_ADQUIRIDO',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class, 'FK_JORNADA', 'PK_JORNADA');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'FK_COMPETENCIAS', 'PK_COMPETENCIAS');
    }
}
