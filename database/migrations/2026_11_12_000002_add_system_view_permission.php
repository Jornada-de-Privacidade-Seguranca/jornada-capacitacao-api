<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ADMIN_PROFILE_ID = 1;

    private const PERMISSION_CODE = 'system.view';

    public function up(): void
    {
        if (! Schema::hasTable('TB_PERMISSOES') || ! Schema::hasTable('RL_PERMISSOES_PERFIS')) {
            return;
        }

        DB::transaction(function (): void {
            $permissionId = DB::table('TB_PERMISSOES')
                ->where('NO_PERMISSOES', self::PERMISSION_CODE)
                ->value('PK_PERMISSOES');

            if ($permissionId === null) {
                $permissionId = DB::table('TB_PERMISSOES')->insertGetId([
                    'NO_PERMISSOES' => self::PERMISSION_CODE,
                    'DS_PERMISSOES_PT' => 'Ver o resumo do sistema',
                    'DS_PERMISSOES_EN' => 'View the system summary',
                ], 'PK_PERMISSOES');
            }

            $adminExists = DB::table('TB_TIPO_PERFIL')
                ->where('PK_TIPO_PERFIL', self::ADMIN_PROFILE_ID)
                ->exists();

            if (! $adminExists) {
                return;
            }

            $alreadyLinked = DB::table('RL_PERMISSOES_PERFIS')
                ->where('FK_TIPO_PERFIL', self::ADMIN_PROFILE_ID)
                ->where('FK_PERMISSOES', $permissionId)
                ->exists();

            if (! $alreadyLinked) {
                DB::table('RL_PERMISSOES_PERFIS')->insert([
                    'FK_TIPO_PERFIL' => self::ADMIN_PROFILE_ID,
                    'FK_PERMISSOES' => $permissionId,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('TB_PERMISSOES') || ! Schema::hasTable('RL_PERMISSOES_PERFIS')) {
            return;
        }

        DB::transaction(function (): void {
            $permissionId = DB::table('TB_PERMISSOES')
                ->where('NO_PERMISSOES', self::PERMISSION_CODE)
                ->value('PK_PERMISSOES');

            if ($permissionId === null) {
                return;
            }

            DB::table('RL_PERMISSOES_PERFIS')
                ->where('FK_PERMISSOES', $permissionId)
                ->delete();

            DB::table('TB_PERMISSOES')
                ->where('PK_PERMISSOES', $permissionId)
                ->delete();
        });
    }
};
