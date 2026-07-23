<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journey extends JornadaModel
{
    protected $table = 'TB_JORNADA';

    protected $primaryKey = 'PK_JORNADA';

    protected $fillable = [
        'FK_USUARIO',
        'FK_PERFIS_JORNADA',
        'FK_EXPERIENCIA_PROFISSIONAL',
        'FK_ATUACAO_PERFIL',
        'FK_PROFICIENCIA_INGLES',
        'FK_CURSO_SUPERIOR',
        'FK_POS_GRADUACAO',
        'DH_CRIADO_EM',
        'DH_FIM',
    ];

    protected $casts = [
        'DH_CRIADO_EM' => 'datetime',
        'DH_FIM' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'FK_USUARIO', 'PK_USUARIO');
    }

    public function journeyProfile(): BelongsTo
    {
        return $this->belongsTo(JourneyProfile::class, 'FK_PERFIS_JORNADA', 'PK_PERFIS_JORNADA');
    }

    public function professionalExperience(): BelongsTo
    {
        return $this->belongsTo(ProfessionalExperience::class, 'FK_EXPERIENCIA_PROFISSIONAL', 'PK_EXPERIENCIA_ATUACAO');
    }

    public function profileExperience(): BelongsTo
    {
        return $this->belongsTo(ProfessionalExperience::class, 'FK_ATUACAO_PERFIL', 'PK_EXPERIENCIA_ATUACAO');
    }

    public function englishProficiency(): BelongsTo
    {
        return $this->belongsTo(EnglishProficiency::class, 'FK_PROFICIENCIA_INGLES', 'PK_PROFICIENCIA_INGLES');
    }

    public function higherEducationCourse(): BelongsTo
    {
        return $this->belongsTo(HigherEducationCourse::class, 'FK_CURSO_SUPERIOR', 'PK_CURSO_SUPERIOR');
    }

    public function postgraduateCourse(): BelongsTo
    {
        return $this->belongsTo(PostgraduateCourse::class, 'FK_POS_GRADUACAO', 'PK_POS_GRADUACAO');
    }

    public function acquiredKnowledges(): HasMany
    {
        return $this->hasMany(AcquiredKnowledge::class, 'FK_JORNADA', 'PK_JORNADA');
    }

    public function professionalCertifications(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfessionalCertification::class,
            'RL_CERTIFICACAO_JORNADA',
            'FK_JORNADA',
            'FK_CERTIFICACAO_PROFISSIONAL',
            'PK_JORNADA',
            'PK_CERTIFICACAO_PROFISSIONAL',
        )->withPivot('PK_CERTIFICACAO_JORNADA');
    }
}
