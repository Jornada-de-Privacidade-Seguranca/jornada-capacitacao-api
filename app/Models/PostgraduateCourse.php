<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PostgraduateCourse extends JornadaModel
{
    protected $table = 'TB_POS_GRADUACAO';

    protected $primaryKey = 'PK_POS_GRADUACAO';

    protected $fillable = [
        'NO_POS_GRADUACAO_PT',
        'NO_POS_GRADUACAO_EN',
        'TP_POS_GRADUACAO',
    ];

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_POS_GRADUACAO', 'PK_POS_GRADUACAO');
    }
}
