<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journey extends JornadaModel
{
    public const ATTRIBUTION_ADMIN = 1;

    public const ATTRIBUTION_SELF = 2;

    protected $table = 'TB_JORNADA';

    protected $primaryKey = 'PK_JORNADA';

    protected $fillable = [
        'FK_USUARIO',
        'FK_USUARIO_ATRIBUIU',
        'FK_PERFIS_JORNADA',
        'FK_EXPERIENCIA_PROFISSIONAL',
        'FK_ATUACAO_PERFIL',
        'FK_SUBCRITERIOS_INGLES',
        'NU_PONTUACAO_TOTAL',
        'TP_JORNADA_ATRIBUIDA',
        'JS_SCORE_BREAKDOWN',
        'DH_CRIADO_EM',
        'DH_FIM',
        'DH_CANCELADA_EM',
    ];

    protected $casts = [
        'NU_PONTUACAO_TOTAL' => 'float',
        'TP_JORNADA_ATRIBUIDA' => 'integer',
        'JS_SCORE_BREAKDOWN' => 'array',
        'DH_CRIADO_EM' => 'datetime',
        'DH_FIM' => 'datetime',
        'DH_CANCELADA_EM' => 'datetime',
    ];

    public function getOriginAttribute(): ?string
    {
        return match ($this->TP_JORNADA_ATRIBUIDA) {
            self::ATTRIBUTION_SELF => 'self_started',
            self::ATTRIBUTION_ADMIN => 'assigned',
            default => null,
        };
    }

    public function usuario(): BelongsTo
    {
        $relation = $this->belongsTo(Usuario::class, 'FK_USUARIO', 'PK_USUARIO');

        if (in_array(SoftDeletes::class, class_uses_recursive(Usuario::class), true)) {
            $relation->withTrashed();
        }

        return $relation;
    }

    public function assignedBy(): BelongsTo
    {
        $relation = $this->belongsTo(Usuario::class, 'FK_USUARIO_ATRIBUIU', 'PK_USUARIO');

        if (in_array(SoftDeletes::class, class_uses_recursive(Usuario::class), true)) {
            $relation->withTrashed();
        }

        return $relation;
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

    public function englishSubcriterion(): BelongsTo
    {
        return $this->belongsTo(Subcriterion::class, 'FK_SUBCRITERIOS_INGLES', 'PK_SUBCRITERIOS');
    }

    public function higherEducationCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            HigherEducationCourse::class,
            'RL_CURSOS_SUPERIORES_JORNADA',
            'FK_JORNADA',
            'FK_CURSO_SUPERIOR',
            'PK_JORNADA',
            'PK_CURSO_SUPERIOR',
        )
            ->withPivot('PK_CURSOS_SUPERIORES_JORNADA')
            ->orderBy('TB_CURSO_SUPERIOR.PK_CURSO_SUPERIOR');
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
