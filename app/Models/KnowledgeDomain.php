<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDomain extends JornadaModel
{
    protected $table = 'TB_DOMINIO_CONHECIMENTO';

    protected $primaryKey = 'PK_DOMINIO_CONHECIMENTO';

    protected $fillable = [
        'NO_DOMINIO_CONHECIMENTO_PT',
        'NO_DOMINIO_CONHECIMENTO_EN',
    ];

    public function journeyProfiles(): HasMany
    {
        return $this->hasMany(JourneyProfile::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }

    public function professionalCertifications(): HasMany
    {
        return $this->hasMany(ProfessionalCertification::class, 'FK_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO');
    }
}
