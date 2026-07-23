<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationJourney extends JornadaModel
{
    protected $table = 'RL_CERTIFICACAO_JORNADA';

    protected $primaryKey = 'PK_CERTIFICACAO_JORNADA';

    protected $fillable = [
        'FK_JORNADA',
        'FK_CERTIFICACAO_PROFISSIONAL',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class, 'FK_JORNADA', 'PK_JORNADA');
    }

    public function professionalCertification(): BelongsTo
    {
        return $this->belongsTo(ProfessionalCertification::class, 'FK_CERTIFICACAO_PROFISSIONAL', 'PK_CERTIFICACAO_PROFISSIONAL');
    }
}
