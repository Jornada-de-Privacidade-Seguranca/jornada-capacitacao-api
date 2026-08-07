<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JourneyProfile extends JornadaModel
{
    use HasFactory;

    protected $table = 'TB_PERFIS_JORNADA';

    protected $primaryKey = 'PK_PERFIS_JORNADA';

    protected $fillable = [
        'FK_DOMINIO_CONHECIMENTO',
        'NO_PERFIS_PT',
        'NO_PERFIS_EN',
        'DS_PERFIS_PT',
        'DS_PERFIS_EN',
    ];

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Competency::class,
            'RL_PERFIS_COMPETENCIAS',
            'FK_PERFIS_JORNADA',
            'FK_COMPETENCIAS',
            'PK_PERFIS_JORNADA',
            'PK_COMPETENCIAS',
        )->withPivot('PK_PERFIS_COMPETENCIAS');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_PERFIS_JORNADA', 'PK_PERFIS_JORNADA');
    }
}
