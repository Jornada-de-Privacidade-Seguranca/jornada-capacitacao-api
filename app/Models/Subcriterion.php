<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcriterion extends JornadaModel
{
    protected $table = 'TB_SUBCRITERIOS';

    protected $primaryKey = 'PK_SUBCRITERIOS';

    protected $fillable = [
        'FK_CRITERIOS',
        'NO_SUBCRITERIO_PT',
        'NO_SUBCRITERIO_EN',
        'NU_PESO_SUBCRITERIO',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'FK_CRITERIOS', 'PK_CRITERIOS');
    }

    public function journeysByEnglishProficiency(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_SUBCRITERIOS_INGLES', 'PK_SUBCRITERIOS');
    }

    public function higherEducationCourses(): HasMany
    {
        return $this->hasMany(HigherEducationCourse::class, 'FK_SUBCRITERIOS', 'PK_SUBCRITERIOS');
    }
}
