<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TB_DOMINIO_CONHECIMENTO', function (Blueprint $table): void {
            $table->increments('PK_DOMINIO_CONHECIMENTO');
            $table->string('NO_DOMINIO_CONHECIMENTO_PT', 100)->nullable();
            $table->string('NO_DOMINIO_CONHECIMENTO_EN', 100)->nullable();
        });

        Schema::create('TB_PERFIS_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_PERFIS_JORNADA');
            $table->unsignedInteger('FK_DOMINIO_CONHECIMENTO')->nullable();
            $table->string('NO_PERFIS_PT', 100)->nullable();
            $table->string('NO_PERFIS_EN', 100)->nullable();
            $table->string('DS_PERFIS_PT', 1000)->nullable();
            $table->string('DS_PERFIS_EN', 1000)->nullable();

            $table->foreign('FK_DOMINIO_CONHECIMENTO')
                ->references('PK_DOMINIO_CONHECIMENTO')
                ->on('TB_DOMINIO_CONHECIMENTO');
        });

        Schema::create('TB_AREA_CONHECIMENTO', function (Blueprint $table): void {
            $table->increments('PK_AREA_CONHECIMENTO');
            $table->string('NO_AREA_CONHECIMENTO_PT', 100)->nullable();
            $table->string('NO_AREA_CONHECIMENTO_EN', 100)->nullable();
            $table->string('DS_AREA_CONHECIMENTO_PT', 500)->nullable();
            $table->string('DS_AREA_CONHECIMENTO_EN', 500)->nullable();
        });

        Schema::create('TB_CRITERIOS', function (Blueprint $table): void {
            $table->increments('PK_CRITERIOS');
            $table->string('NO_CRITERIO_PT', 100)->nullable();
            $table->string('NO_CRITERIO_EN', 100)->nullable();
            $table->double('NU_PESO_CRITERIO')->nullable();
        });

        Schema::create('TB_SUBCRITERIOS', function (Blueprint $table): void {
            $table->increments('PK_SUBCRITERIOS');
            $table->unsignedInteger('FK_CRITERIOS')->nullable();
            $table->string('NO_SUBCRITERIO_PT', 100)->nullable();
            $table->string('NO_SUBCRITERIO_EN', 100)->nullable();
            $table->double('NU_PESO_SUBCRITERIO')->nullable();

            $table->foreign('FK_CRITERIOS')->references('PK_CRITERIOS')->on('TB_CRITERIOS');
        });

        Schema::create('TB_PESO_CRIT_COMPETENCIA', function (Blueprint $table): void {
            $table->increments('PK_PESO_CRIT_COMPETENCIA');
            $table->unsignedInteger('FK_PERFIS_JORNADA')->nullable();
            $table->unsignedInteger('FK_AREA_CONHECIMENTO')->nullable();
            $table->double('NU_PESO_CRIT_COMPETENCIA')->nullable();

            $table->foreign('FK_PERFIS_JORNADA')->references('PK_PERFIS_JORNADA')->on('TB_PERFIS_JORNADA');
            $table->foreign('FK_AREA_CONHECIMENTO')->references('PK_AREA_CONHECIMENTO')->on('TB_AREA_CONHECIMENTO');
        });

        Schema::create('TB_DIFICULDADE', function (Blueprint $table): void {
            $table->increments('PK_DIFICULDADE');
            $table->string('NO_DIFICULDADE_PT', 20)->nullable();
            $table->string('NO_DIFICULDADE_EN', 20)->nullable();
        });

        Schema::create('TB_COMPETENCIAS', function (Blueprint $table): void {
            $table->increments('PK_COMPETENCIAS');
            $table->unsignedInteger('FK_DOMINIO_CONHECIMENTO')->nullable();
            $table->unsignedInteger('FK_AREA_CONHECIMENTO')->nullable();
            $table->unsignedInteger('FK_DIFICULDADE')->nullable();
            $table->string('NO_ORIGEM_COMPETENCIAS_PT', 50)->nullable();
            $table->string('NO_ORIGEM_COMPETENCIAS_EN', 50)->nullable();
            $table->string('NO_COMPETENCIAS_PT', 100)->nullable();
            $table->string('NO_COMPETENCIAS_EN', 100)->nullable();
            $table->string('DS_COMPETENCIAS_PT', 500)->nullable();
            $table->string('DS_COMPETENCIAS_EN', 500)->nullable();

            $table->foreign('FK_AREA_CONHECIMENTO')->references('PK_AREA_CONHECIMENTO')->on('TB_AREA_CONHECIMENTO');
            $table->foreign('FK_DIFICULDADE')->references('PK_DIFICULDADE')->on('TB_DIFICULDADE');
            $table->foreign('FK_DOMINIO_CONHECIMENTO')
                ->references('PK_DOMINIO_CONHECIMENTO')
                ->on('TB_DOMINIO_CONHECIMENTO');
        });

        Schema::create('RL_PERFIS_COMPETENCIAS', function (Blueprint $table): void {
            $table->increments('PK_PERFIS_COMPETENCIAS');
            $table->unsignedInteger('FK_PERFIS_JORNADA')->nullable();
            $table->unsignedInteger('FK_COMPETENCIAS')->nullable();

            $table->foreign('FK_PERFIS_JORNADA')->references('PK_PERFIS_JORNADA')->on('TB_PERFIS_JORNADA');
            $table->foreign('FK_COMPETENCIAS')->references('PK_COMPETENCIAS')->on('TB_COMPETENCIAS');
        });

        Schema::create('TB_CURSO_SUPERIOR', function (Blueprint $table): void {
            $table->increments('PK_CURSO_SUPERIOR');
            $table->unsignedInteger('FK_SUBCRITERIOS')->nullable();
            $table->string('NO_CURSO_SUPERIOR_PT', 100)->nullable();
            $table->string('NO_CURSO_SUPERIOR_EN', 100)->nullable();

            $table->foreign('FK_SUBCRITERIOS')->references('PK_SUBCRITERIOS')->on('TB_SUBCRITERIOS');
        });

        Schema::create('TB_CERTIFICACAO_PROFISSIONAL', function (Blueprint $table): void {
            $table->increments('PK_CERTIFICACAO_PROFISSIONAL');
            $table->unsignedInteger('FK_DOMINIO_CONHECIMENTO')->nullable();
            $table->string('NO_CERTIFICACAO_PROFISSIONAL', 100)->nullable();
            $table->string('NO_EMISSOR_CERTIFICACAO', 100)->nullable();

            $table->foreign('FK_DOMINIO_CONHECIMENTO')
                ->references('PK_DOMINIO_CONHECIMENTO')
                ->on('TB_DOMINIO_CONHECIMENTO');
        });

        Schema::create('TB_EXPERIENCIA_ATUACAO', function (Blueprint $table): void {
            $table->increments('PK_EXPERIENCIA_ATUACAO');
            $table->string('DS_EXPERIENCIA_ATUACAO_PT', 20)->nullable();
            $table->string('DS_EXPERIENCIA_ATUACAO_EN', 30)->nullable();
        });

        Schema::create('TB_TIPO_PERFIL', function (Blueprint $table): void {
            $table->increments('PK_TIPO_PERFIL');
            $table->string('NO_TIPO_PERFIL', 20)->nullable();
        });

        Schema::create('TB_USUARIO', function (Blueprint $table): void {
            $table->increments('PK_USUARIO');
            $table->unsignedInteger('FK_TIPO_PERFIL')->nullable();
            $table->integer('FK_TIPO_USUARIO')->nullable();
            $table->string('NO_USUARIO', 50)->nullable();
            $table->string('NO_ORGAO_EMPRESA', 100)->nullable();
            $table->string('NO_SETOR', 100)->nullable();
            $table->string('NU_MATRICULA', 50)->nullable();
            $table->string('DS_EMAIL', 100)->nullable();
            $table->string('DS_SENHA', 255)->nullable();
            $table->timestamp('DH_CRIADO_EM')->nullable()->useCurrent();
            $table->integer('ST_ATIVO')->nullable();

            $table->foreign('FK_TIPO_PERFIL')->references('PK_TIPO_PERFIL')->on('TB_TIPO_PERFIL');
        });

        // As permissoes dependem de tabelas criadas em migration posterior, que insere esse trecho
        $fixedData = file_get_contents(database_path('sql/jornada_fixed_data.sql'));
        $permissionsStart = strpos($fixedData, '-- Insert TB_PERMISSOES');
        DB::unprepared($permissionsStart === false ? $fixedData : substr($fixedData, 0, $permissionsStart));

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX "TB_USUARIO_DS_EMAIL_LOWER_UNIQUE" ON "TB_USUARIO" (LOWER("DS_EMAIL")) WHERE "DS_EMAIL" IS NOT NULL');
        }

        DB::table('TB_USUARIO')->insert([
            [
                'FK_TIPO_PERFIL' => 1,
                'FK_TIPO_USUARIO' => 1,
                'NO_USUARIO' => 'Administrador Sistema',
                'NO_ORGAO_EMPRESA' => 'Jornada',
                'NO_SETOR' => 'Administracao',
                'NU_MATRICULA' => null,
                'DS_EMAIL' => 'admin@jornada.local',
                'DS_SENHA' => Hash::make('admin123'),
                'DH_CRIADO_EM' => now(),
                'ST_ATIVO' => 1,
            ],
            [
                'FK_TIPO_PERFIL' => 3,
                'FK_TIPO_USUARIO' => 2,
                'NO_USUARIO' => 'Usuario Teste',
                'NO_ORGAO_EMPRESA' => 'Jornada',
                'NO_SETOR' => 'Testes',
                'NU_MATRICULA' => null,
                'DS_EMAIL' => 'user@jornada.local',
                'DS_SENHA' => Hash::make('usere123'),
                'DH_CRIADO_EM' => now(),
                'ST_ATIVO' => 1,
            ],
        ]);

        Schema::create('TB_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_JORNADA');
            $table->unsignedInteger('FK_USUARIO')->nullable();
            $table->unsignedInteger('FK_PERFIS_JORNADA')->nullable();
            $table->unsignedInteger('FK_EXPERIENCIA_PROFISSIONAL')->nullable();
            $table->unsignedInteger('FK_ATUACAO_PERFIL')->nullable();
            $table->unsignedInteger('FK_SUBCRITERIOS_INGLES')->nullable();
            $table->integer('NU_PONTUACAO_TOTAL')->nullable();
            $table->timestamp('DH_CRIADO_EM')->nullable()->useCurrent();
            $table->timestamp('DH_FIM')->nullable();

            $table->foreign('FK_USUARIO')->references('PK_USUARIO')->on('TB_USUARIO');
            $table->foreign('FK_PERFIS_JORNADA')->references('PK_PERFIS_JORNADA')->on('TB_PERFIS_JORNADA');
            $table->foreign('FK_EXPERIENCIA_PROFISSIONAL')->references('PK_EXPERIENCIA_ATUACAO')->on('TB_EXPERIENCIA_ATUACAO');
            $table->foreign('FK_ATUACAO_PERFIL')->references('PK_EXPERIENCIA_ATUACAO')->on('TB_EXPERIENCIA_ATUACAO');
            $table->foreign('FK_SUBCRITERIOS_INGLES')->references('PK_SUBCRITERIOS')->on('TB_SUBCRITERIOS');
        });

        Schema::create('TB_CONHECIMENTOS_ADQUIRIDOS', function (Blueprint $table): void {
            $table->increments('PK_CONHECIMENTOS_ADQUIRIDOS');
            $table->unsignedInteger('FK_JORNADA')->nullable();
            $table->unsignedInteger('FK_COMPETENCIAS')->nullable();
            $table->integer('TP_ADQUIRIDO')->nullable();

            $table->foreign('FK_JORNADA')->references('PK_JORNADA')->on('TB_JORNADA');
            $table->foreign('FK_COMPETENCIAS')->references('PK_COMPETENCIAS')->on('TB_COMPETENCIAS');
        });

        Schema::create('RL_CERTIFICACAO_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_CERTIFICACAO_JORNADA');
            $table->unsignedInteger('FK_JORNADA')->nullable();
            $table->unsignedInteger('FK_CERTIFICACAO_PROFISSIONAL')->nullable();

            $table->foreign('FK_JORNADA')->references('PK_JORNADA')->on('TB_JORNADA');
            $table->foreign('FK_CERTIFICACAO_PROFISSIONAL')->references('PK_CERTIFICACAO_PROFISSIONAL')->on('TB_CERTIFICACAO_PROFISSIONAL');
        });

        Schema::create('RL_CURSOS_SUPERIORES_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_CURSOS_SUPERIORES_JORNADA');
            $table->unsignedInteger('FK_JORNADA')->nullable();
            $table->unsignedInteger('FK_CURSO_SUPERIOR')->nullable();

            $table->foreign('FK_JORNADA')->references('PK_JORNADA')->on('TB_JORNADA');
            $table->foreign('FK_CURSO_SUPERIOR')->references('PK_CURSO_SUPERIOR')->on('TB_CURSO_SUPERIOR');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RL_CURSOS_SUPERIORES_JORNADA');
        Schema::dropIfExists('RL_CERTIFICACAO_JORNADA');
        Schema::dropIfExists('TB_CONHECIMENTOS_ADQUIRIDOS');
        Schema::dropIfExists('TB_JORNADA');
        Schema::dropIfExists('TB_USUARIO');
        Schema::dropIfExists('TB_TIPO_PERFIL');
        Schema::dropIfExists('TB_EXPERIENCIA_ATUACAO');
        Schema::dropIfExists('TB_CERTIFICACAO_PROFISSIONAL');
        Schema::dropIfExists('TB_CURSO_SUPERIOR');
        Schema::dropIfExists('RL_PERFIS_COMPETENCIAS');
        Schema::dropIfExists('TB_COMPETENCIAS');
        Schema::dropIfExists('TB_DIFICULDADE');
        Schema::dropIfExists('TB_PESO_CRIT_COMPETENCIA');
        Schema::dropIfExists('TB_SUBCRITERIOS');
        Schema::dropIfExists('TB_CRITERIOS');
        Schema::dropIfExists('TB_AREA_CONHECIMENTO');
        Schema::dropIfExists('TB_PERFIS_JORNADA');
        Schema::dropIfExists('TB_DOMINIO_CONHECIMENTO');
    }
};
