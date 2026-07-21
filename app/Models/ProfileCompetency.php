<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileCompetency extends JornadaModel
{
    protected $table = 'RL_PERFIS_COMPETENCIAS';

    protected $primaryKey = 'PK_PERFIS_COMPETENCIAS';

    protected $fillable = [
        'FK_PERFIS_JORNADA',
        'FK_COMPETENCIAS',
    ];

    public function journeyProfile(): BelongsTo
    {
        return $this->belongsTo(JourneyProfile::class, 'FK_PERFIS_JORNADA', 'PK_PERFIS_JORNADA');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'FK_COMPETENCIAS', 'PK_COMPETENCIAS');
    }
}
