<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TB_PERFIS_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_PERFIS_JORNADA');
            $table->string('DS_PERFIS_PT', 1000)->nullable();
            $table->string('DS_PERFIS_EN', 1000)->nullable();
            $table->integer('TP_PERFIS')->nullable();
        });

        Schema::create('TB_AREA_CONHECIMENTO', function (Blueprint $table): void {
            $table->increments('PK_AREA_CONHECIMENTO');
            $table->string('NO_AREA_CONHECIMENTO_PT', 100)->nullable();
            $table->string('NO_AREA_CONHECIMENTO_EN', 100)->nullable();
        });

        Schema::create('TB_DIFICULDADE', function (Blueprint $table): void {
            $table->increments('PK_DIFICULDADE');
            $table->string('DS_DIFICULDADE_PT', 20)->nullable();
            $table->string('DS_DIFICULDADE_EN', 20)->nullable();
        });

        Schema::create('TB_COMPETENCIAS', function (Blueprint $table): void {
            $table->increments('PK_COMPETENCIAS');
            $table->unsignedInteger('FK_AREA_CONHECIMENTO')->nullable();
            $table->unsignedInteger('FK_DIFICULDADE')->nullable();
            $table->string('NO_ORIGEM_COMPETENCIAS_PT', 50)->nullable();
            $table->string('NO_ORIGEM_COMPETENCIAS_EN', 50)->nullable();
            $table->string('NO_COMPETENCIAS_PT', 100)->nullable();
            $table->string('NO_COMPETENCIAS_EN', 100)->nullable();
            $table->string('DS_COMPETENCIAS_PT', 500)->nullable();
            $table->string('DS_COMPETENCIAS_EN', 500)->nullable();
            $table->integer('TP_COMPETENCIAS')->nullable();

            $table->foreign('FK_AREA_CONHECIMENTO')->references('PK_AREA_CONHECIMENTO')->on('TB_AREA_CONHECIMENTO');
            $table->foreign('FK_DIFICULDADE')->references('PK_DIFICULDADE')->on('TB_DIFICULDADE');
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
            $table->string('NO_CURSO_SUPERIOR_PT', 100)->nullable();
            $table->string('NO_CURSO_SUPERIOR_EN', 100)->nullable();
            $table->integer('TP_CURSO_SUPERIOR')->nullable();
        });

        Schema::create('TB_POS_GRADUACAO', function (Blueprint $table): void {
            $table->increments('PK_POS_GRADUACAO');
            $table->string('NO_POS_GRADUACAO_PT', 100)->nullable();
            $table->string('NO_POS_GRADUACAO_EN', 100)->nullable();
            $table->integer('TP_POS_GRADUACAO')->nullable();
        });

        Schema::create('TB_CERTIFICACAO_PROFISSIONAL', function (Blueprint $table): void {
            $table->increments('PK_CERTIFICACAO_PROFISSIONAL');
            $table->string('NO_CERTIFICACAO_PROFISSIONAL', 100)->nullable();
        });

        Schema::create('TB_PROFICIENCIA_INGLES', function (Blueprint $table): void {
            $table->increments('PK_PROFICIENCIA_INGLES');
            $table->string('NO_PROFICIENCIA_INGLES_PT', 20)->nullable();
            $table->string('NO_PROFICIENCIA_INGLES_EN', 20)->nullable();
        });

        Schema::create('TB_EXPERIENCIA_ATUACAO', function (Blueprint $table): void {
            $table->increments('PK_EXPERIENCIA_ATUACAO');
            $table->string('DS_EXPERIENCIA_ATUACAO_PT', 20)->nullable();
            $table->string('DS_EXPERIENCIA_ATUACAO_EN', 20)->nullable();
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
            $table->string('DS_EMAIL', 50)->nullable();
            $table->string('DS_SENHA', 50)->nullable();
            $table->timestamp('DH_CRIADO_EM')->nullable()->useCurrent();
            $table->integer('ST_ATIVO')->nullable();

            $table->foreign('FK_TIPO_PERFIL')->references('PK_TIPO_PERFIL')->on('TB_TIPO_PERFIL');
        });

        Schema::create('TB_JORNADA', function (Blueprint $table): void {
            $table->increments('PK_JORNADA');
            $table->unsignedInteger('FK_USUARIO')->nullable();
            $table->unsignedInteger('FK_PERFIS_JORNADA')->nullable();
            $table->unsignedInteger('FK_EXPERIENCIA_PROFISSIONAL')->nullable();
            $table->unsignedInteger('FK_ATUACAO_PERFIL')->nullable();
            $table->unsignedInteger('FK_PROFICIENCIA_INGLES')->nullable();
            $table->unsignedInteger('FK_CURSO_SUPERIOR')->nullable();
            $table->unsignedInteger('FK_POS_GRADUACAO')->nullable();
            $table->timestamp('DH_CRIADO_EM')->nullable()->useCurrent();
            $table->timestamp('DH_FIM')->nullable();

            $table->foreign('FK_USUARIO')->references('PK_USUARIO')->on('TB_USUARIO');
            $table->foreign('FK_PERFIS_JORNADA')->references('PK_PERFIS_JORNADA')->on('TB_PERFIS_JORNADA');
            $table->foreign('FK_EXPERIENCIA_PROFISSIONAL')->references('PK_EXPERIENCIA_ATUACAO')->on('TB_EXPERIENCIA_ATUACAO');
            $table->foreign('FK_ATUACAO_PERFIL')->references('PK_EXPERIENCIA_ATUACAO')->on('TB_EXPERIENCIA_ATUACAO');
            $table->foreign('FK_PROFICIENCIA_INGLES')->references('PK_PROFICIENCIA_INGLES')->on('TB_PROFICIENCIA_INGLES');
            $table->foreign('FK_CURSO_SUPERIOR')->references('PK_CURSO_SUPERIOR')->on('TB_CURSO_SUPERIOR');
            $table->foreign('FK_POS_GRADUACAO')->references('PK_POS_GRADUACAO')->on('TB_POS_GRADUACAO');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('RL_CERTIFICACAO_JORNADA');
        Schema::dropIfExists('TB_CONHECIMENTOS_ADQUIRIDOS');
        Schema::dropIfExists('TB_JORNADA');
        Schema::dropIfExists('TB_USUARIO');
        Schema::dropIfExists('TB_TIPO_PERFIL');
        Schema::dropIfExists('TB_EXPERIENCIA_ATUACAO');
        Schema::dropIfExists('TB_PROFICIENCIA_INGLES');
        Schema::dropIfExists('TB_CERTIFICACAO_PROFISSIONAL');
        Schema::dropIfExists('TB_POS_GRADUACAO');
        Schema::dropIfExists('TB_CURSO_SUPERIOR');
        Schema::dropIfExists('RL_PERFIS_COMPETENCIAS');
        Schema::dropIfExists('TB_COMPETENCIAS');
        Schema::dropIfExists('TB_DIFICULDADE');
        Schema::dropIfExists('TB_AREA_CONHECIMENTO');
        Schema::dropIfExists('TB_PERFIS_JORNADA');
    }
};
