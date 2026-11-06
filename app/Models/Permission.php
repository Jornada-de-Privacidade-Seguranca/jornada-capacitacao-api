<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends JornadaModel
{
    protected $table = 'TB_PERMISSOES';

    protected $primaryKey = 'PK_PERMISSOES';

    /**
     * NO_PERMISSOES guarda o codigo unico da acao (por exemplo, users.manage).
     */
    protected $fillable = [
        'NO_PERMISSOES',
        'DS_PERMISSOES_PT',
        'DS_PERMISSOES_EN',
    ];

    public function profileTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProfileType::class,
            'RL_PERMISSOES_PERFIS',
            'FK_PERMISSOES',
            'FK_TIPO_PERFIL',
            'PK_PERMISSOES',
            'PK_TIPO_PERFIL',
        );
    }
}
