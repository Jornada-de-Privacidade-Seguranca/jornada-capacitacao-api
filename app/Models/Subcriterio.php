<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcriterio extends JornadaModel
{
    protected $table = 'TB_SUBCRITERIOS';

    protected $primaryKey = 'PK_SUBCRITERIOS';

    protected $fillable = [
        'NO_SUBCRITERIOS_PT',
        'NO_SUBCRITERIOS_EN',
    ];

    public function higherEducationCourses(): HasMany
    {
        return $this->hasMany(HigherEducationCourse::class, 'TP_CURSO_SUPERIOR', 'PK_SUBCRITERIOS');
    }
}
