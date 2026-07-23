<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfessionalExperience extends JornadaModel
{
    protected $table = 'TB_EXPERIENCIA_ATUACAO';

    protected $primaryKey = 'PK_EXPERIENCIA_ATUACAO';

    protected $fillable = [
        'DS_EXPERIENCIA_ATUACAO_PT',
        'DS_EXPERIENCIA_ATUACAO_EN',
    ];

    public function professionalExperienceJourneys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_EXPERIENCIA_PROFISSIONAL', 'PK_EXPERIENCIA_ATUACAO');
    }

    public function profileExperienceJourneys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_ATUACAO_PERFIL', 'PK_EXPERIENCIA_ATUACAO');
    }
}
