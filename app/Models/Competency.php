<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competency extends JornadaModel
{
    use HasFactory;

    protected $table = 'TB_COMPETENCIAS';

    protected $primaryKey = 'PK_COMPETENCIAS';

    protected $fillable = [
        'FK_DOMINIO_CONHECIMENTO',
        'FK_AREA_CONHECIMENTO',
        'FK_DIFICULDADE',
        'NO_ORIGEM_COMPETENCIAS_PT',
        'NO_ORIGEM_COMPETENCIAS_EN',
        'NO_COMPETENCIAS_PT',
        'NO_COMPETENCIAS_EN',
        'DS_COMPETENCIAS_PT',
        'DS_COMPETENCIAS_EN',
    ];

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }

    public function knowledgeArea(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArea::class, 'FK_AREA_CONHECIMENTO', 'PK_AREA_CONHECIMENTO');
    }

    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(Difficulty::class, 'FK_DIFICULDADE', 'PK_DIFICULDADE');
    }

    public function journeyProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            JourneyProfile::class,
            'RL_PERFIS_COMPETENCIAS',
            'FK_COMPETENCIAS',
            'FK_PERFIS_JORNADA',
            'PK_COMPETENCIAS',
            'PK_PERFIS_JORNADA',
        )->withPivot('PK_PERFIS_COMPETENCIAS');
    }
}
