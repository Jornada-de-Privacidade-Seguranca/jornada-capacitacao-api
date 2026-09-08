<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyCriterionWeight extends JornadaModel
{
    protected $table = 'TB_PESO_CRIT_COMPETENCIA';

    protected $primaryKey = 'PK_PESO_CRIT_COMPETENCIA';

    protected $fillable = [
        'FK_PERFIS_JORNADA',
        'FK_AREA_CONHECIMENTO',
        'NU_PESO_CRIT_COMPETENCIA',
    ];

    public function journeyProfile(): BelongsTo
    {
        return $this->belongsTo(JourneyProfile::class, 'FK_PERFIS_JORNADA', 'PK_PERFIS_JORNADA');
    }

    public function knowledgeArea(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArea::class, 'FK_AREA_CONHECIMENTO', 'PK_AREA_CONHECIMENTO');
    }
}
