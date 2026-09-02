<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Difficulty extends JornadaModel
{
    protected $table = 'TB_DIFICULDADE';

    protected $primaryKey = 'PK_DIFICULDADE';

    protected $fillable = [
        'NO_DIFICULDADE_PT',
        'NO_DIFICULDADE_EN',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class, 'FK_DIFICULDADE', 'PK_DIFICULDADE');
    }
}
