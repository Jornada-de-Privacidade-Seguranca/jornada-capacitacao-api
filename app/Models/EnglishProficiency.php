<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishProficiency extends JornadaModel
{
    protected $table = 'TB_PROFICIENCIA_INGLES';

    protected $primaryKey = 'PK_PROFICIENCIA_INGLES';

    protected $fillable = [
        'NO_PROFICIENCIA_INGLES_PT',
        'NO_PROFICIENCIA_INGLES_EN',
    ];

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_PROFICIENCIA_INGLES', 'PK_PROFICIENCIA_INGLES');
    }
}
