<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends JornadaModel
{
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
