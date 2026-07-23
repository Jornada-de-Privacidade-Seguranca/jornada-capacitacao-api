<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfileType extends JornadaModel
{
    protected $table = 'TB_TIPO_PERFIL';

    protected $primaryKey = 'PK_TIPO_PERFIL';

    protected $fillable = [
        'NO_TIPO_PERFIL',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(Usuario::class, 'FK_TIPO_PERFIL', 'PK_TIPO_PERFIL');
    }
}
