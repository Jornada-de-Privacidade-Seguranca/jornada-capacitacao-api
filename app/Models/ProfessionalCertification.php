<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProfessionalCertification extends JornadaModel
{
    protected $table = 'TB_CERTIFICACAO_PROFISSIONAL';

    protected $primaryKey = 'PK_CERTIFICACAO_PROFISSIONAL';

    protected $fillable = [
        'FK_DOMINIO_CONHECIMENTO',
        'NO_CERTIFICACAO_PROFISSIONAL',
    ];

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }

    public function journeys(): BelongsToMany
    {
        return $this->belongsToMany(
            Journey::class,
            'RL_CERTIFICACAO_JORNADA',
            'FK_CERTIFICACAO_PROFISSIONAL',
            'FK_JORNADA',
            'PK_CERTIFICACAO_PROFISSIONAL',
            'PK_JORNADA',
        )->withPivot('PK_CERTIFICACAO_JORNADA');
    }
}
