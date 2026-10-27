<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends JornadaModel
{
    public const ENGLISH_PROFICIENCY_NAME = 'Proficiency Level - English';

    public const EDUCATION_NAME = 'Education';

    public const PROFESSIONAL_EXPERIENCE_NAME = 'Professional Experience';

    public const PROFESSIONAL_CERTIFICATION_NAME = 'Professional Certification';

    protected $table = 'TB_CRITERIOS';

    protected $primaryKey = 'PK_CRITERIOS';

    protected $fillable = [
        'NO_CRITERIO_PT',
        'NO_CRITERIO_EN',
        'NU_PESO_CRITERIO',
    ];

    public function subcriteria(): HasMany
    {
        return $this->hasMany(Subcriterion::class, 'FK_CRITERIOS', 'PK_CRITERIOS');
    }
}
