<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ADMIN_USER_TYPE = 1;

    private const ADMIN_PROFILE_TYPE = 1;

    private const USER_PROFILE_TYPE = 3;

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->createPermissionTables();
            $this->createProfileChangeHistoryTable();
            $this->insertPermissions();
            $this->assignProfilesFromUserType();
        });
    }

    /**
     * Remove as tabelas criadas. Os perfis atribuidos aos usuarios em
     * assignProfilesFromUserType nao sao restaurados, porque o valor anterior
     * nao fica registrado.
     */
    public function down(): void
    {
        Schema::dropIfExists('TH_ALTERACAO_PERFIS');
        Schema::dropIfExists('RL_PERMISSOES_PERFIS');
        Schema::dropIfExists('TB_PERMISSOES');
    }

    private function createPermissionTables(): void
    {
        Schema::create('TB_PERMISSOES', function (Blueprint $table): void {
            $table->increments('PK_PERMISSOES');
            $table->string('NO_PERMISSOES', 50);
            $table->string('DS_PERMISSOES_PT', 200)->nullable();
            $table->string('DS_PERMISSOES_EN', 200)->nullable();

            $table->unique('NO_PERMISSOES', 'UK_PERMISSOES_NO_PERMISSOES');
        });

        Schema::create('RL_PERMISSOES_PERFIS', function (Blueprint $table): void {
            $table->increments('PK_PERMISSOES_PERFIS');
            $table->unsignedInteger('FK_TIPO_PERFIL');
            $table->unsignedInteger('FK_PERMISSOES');

            $table->unique(['FK_TIPO_PERFIL', 'FK_PERMISSOES'], 'UK_PERMISSOES_PERFIS');
            $table->foreign('FK_TIPO_PERFIL', 'FK_TIPO_PERFIL_PERMISSAO')
                ->references('PK_TIPO_PERFIL')
                ->on('TB_TIPO_PERFIL');
            $table->foreign('FK_PERMISSOES', 'FK_TIPO_PERMISSAO_PERFIL')
                ->references('PK_PERMISSOES')
                ->on('TB_PERMISSOES');
        });
    }

    private function createProfileChangeHistoryTable(): void
    {
        Schema::create('TH_ALTERACAO_PERFIS', function (Blueprint $table): void {
            $table->increments('PK_ALTERACAO_PERFIS');
            $table->unsignedInteger('FK_USUARIO_ALTERADO');
            $table->unsignedInteger('FK_TIPO_PERFIL_ANTERIOR')->nullable();
            $table->unsignedInteger('FK_TIPO_PERFIL_NOVO');
            $table->unsignedInteger('FK_USUARIO_ADM');
            $table->timestamp('DH_REGISTRO')->useCurrent();

            // Sem exclusao em cascata: o historico e mantido, ja que a exclusao de usuarios e logica
            $table->foreign('FK_USUARIO_ALTERADO', 'FK_USUARIO_ALTERADO')
                ->references('PK_USUARIO')
                ->on('TB_USUARIO');
            $table->foreign('FK_USUARIO_ADM', 'FK_USUARIO_ADM')
                ->references('PK_USUARIO')
                ->on('TB_USUARIO');
            $table->foreign('FK_TIPO_PERFIL_ANTERIOR', 'FK_TIPO_PERFIL_ANTERIOR')
                ->references('PK_TIPO_PERFIL')
                ->on('TB_TIPO_PERFIL');
            $table->foreign('FK_TIPO_PERFIL_NOVO', 'FK_TIPO_PERFIL_NOVO')
                ->references('PK_TIPO_PERFIL')
                ->on('TB_TIPO_PERFIL');
        });
    }

    /**
     * Insere as permissoes e a ligacao com os perfis a partir do trecho de
     * jornada_fixed_data.sql, que fica igual ao Script_Insert_Jornada.sql.
     */
    private function insertPermissions(): void
    {
        $sql = file_get_contents(database_path('sql/jornada_fixed_data.sql'));
        $start = strpos($sql, '-- Insert TB_PERMISSOES');
        $end = strpos($sql, '-- Fim RL_PERMISSOES_PERFIS');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Trecho de permissoes nao encontrado em jornada_fixed_data.sql.');
        }

        DB::unprepared(substr($sql, $start, $end - $start));
    }

    /**
     * Administradores (FK_TIPO_USUARIO = 1) recebem o perfil Administrador, e
     * os demais, o perfil Usuario, inclusive quem se cadastrou escolhendo outro.
     */
    private function assignProfilesFromUserType(): void
    {
        DB::table('TB_USUARIO')
            ->where('FK_TIPO_USUARIO', self::ADMIN_USER_TYPE)
            ->update(['FK_TIPO_PERFIL' => self::ADMIN_PROFILE_TYPE]);

        DB::table('TB_USUARIO')
            ->where(function ($query): void {
                $query->where('FK_TIPO_USUARIO', '!=', self::ADMIN_USER_TYPE)
                    ->orWhereNull('FK_TIPO_USUARIO');
            })
            ->update(['FK_TIPO_PERFIL' => self::USER_PROFILE_TYPE]);
    }
};
