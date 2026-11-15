<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Usuario extends JornadaModel implements AuthenticatableContract
{
    use Authenticatable;
    use SoftDeletes;

    public const DELETED_AT = 'DH_EXCLUIDO_EM';

    protected $table = 'TB_USUARIO';

    protected $primaryKey = 'PK_USUARIO';

    protected $fillable = [
        'FK_TIPO_PERFIL',
        'FK_PERFIL_JORNADA',
        'FK_TIPO_USUARIO',
        'NO_USUARIO',
        'NO_ORGAO_EMPRESA',
        'NO_SETOR',
        'NU_MATRICULA',
        'DS_EMAIL',
        'DS_SENHA',
        'DH_CRIADO_EM',
        'DH_EXCLUIDO_EM',
        'ST_ATIVO',
        'TP_ATIVACAO',
        'DH_ATIVACAO',
    ];

    protected $hidden = [
        'DS_SENHA',
    ];

    protected $casts = [
        'DH_CRIADO_EM' => 'datetime',
        'DH_ATIVACAO' => 'datetime',
        'DH_EXCLUIDO_EM' => 'datetime',
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

    /**
     * @return Collection<int, string>
     */
    public function permissionCodes(): Collection
    {
        return $this->profileType?->permissions
            ?->pluck('NO_PERMISSOES')
            ->sort()
            ->values() ?? collect();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissionCodes()->contains($permission);
    }

    public function selectedJourneyProfile(): BelongsTo
    {
        return $this->belongsTo(JourneyProfile::class, 'FK_PERFIL_JORNADA', 'PK_PERFIS_JORNADA');
    }

    public function journeys(): HasMany
    {
        return $this->hasMany(Journey::class, 'FK_USUARIO', 'PK_USUARIO');
    }

    public function profileTypeChanges(): HasMany
    {
        return $this->hasMany(ProfileTypeChange::class, 'FK_USUARIO_ALTERADO', 'PK_USUARIO');
    }
}
