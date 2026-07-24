<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usuario extends JornadaModel implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'TB_USUARIO';

    protected $primaryKey = 'PK_USUARIO';

    protected $fillable = [
        'FK_TIPO_PERFIL',
        'FK_TIPO_USUARIO',
        'NO_USUARIO',
        'NO_ORGAO_EMPRESA',
        'NO_SETOR',
        'NU_MATRICULA',
        'DS_EMAIL',
        'DS_SENHA',
        'DH_CRIADO_EM',
        'ST_ATIVO',
    ];

    protected $hidden = [
        'DS_SENHA',
    ];

    protected $casts = [
        'DH_CRIADO_EM' => 'datetime',
        'ST_ATIVO' => 'integer',
    ];

    public function getAuthPasswordName(): string
    {
        return 'DS_SENHA';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->DS_SENHA;
    }

    public function profileType(): BelongsTo
    {
        return $this->belongsTo(ProfileType::class, 'FK_TIPO_PERFIL', 'PK_TIPO_PERFIL');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_USUARIO', 'PK_USUARIO');
    }
}
