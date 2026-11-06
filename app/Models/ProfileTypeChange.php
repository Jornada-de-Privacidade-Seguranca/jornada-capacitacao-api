<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileTypeChange extends JornadaModel
{
    protected $table = 'TH_ALTERACAO_PERFIS';

    protected $primaryKey = 'PK_ALTERACAO_PERFIS';

    protected $fillable = [
        'FK_USUARIO_ALTERADO',
        'FK_TIPO_PERFIL_ANTERIOR',
        'FK_TIPO_PERFIL_NOVO',
        'FK_USUARIO_ADM',
        'DH_REGISTRO',
    ];

    protected $casts = [
        'DH_REGISTRO' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'FK_USUARIO_ALTERADO', 'PK_USUARIO');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'FK_USUARIO_ADM', 'PK_USUARIO');
    }

    /**
     * Nulo na primeira atribuicao de perfil.
     */
    public function previousProfileType(): BelongsTo
    {
        return $this->belongsTo(ProfileType::class, 'FK_TIPO_PERFIL_ANTERIOR', 'PK_TIPO_PERFIL');
    }

    public function newProfileType(): BelongsTo
    {
        return $this->belongsTo(ProfileType::class, 'FK_TIPO_PERFIL_NOVO', 'PK_TIPO_PERFIL');
    }
}
