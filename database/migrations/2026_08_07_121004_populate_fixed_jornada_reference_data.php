<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TB_PERFIS_JORNADA', function (Blueprint $table): void {
            if (! Schema::hasColumn('TB_PERFIS_JORNADA', 'NO_PERFIS_PT')) {
                $table->string('NO_PERFIS_PT', 100)->nullable()->after('FK_DOMINIO_CONHECIMENTO');
            }

            if (! Schema::hasColumn('TB_PERFIS_JORNADA', 'NO_PERFIS_EN')) {
                $table->string('NO_PERFIS_EN', 100)->nullable()->after('NO_PERFIS_PT');
            }
        });

        $hasFixedJourneyProfiles = DB::table('TB_PERFIS_JORNADA')
            ->where('NO_PERFIS_PT', 'Encarregado pelo Tratamento de Dados Pessoais')
            ->exists();

        if (! $hasFixedJourneyProfiles) {
            $this->ensureLegacyFixedReferenceTables();
            $this->insertFixedReferenceData();
        }
    }

    public function down(): void
    {
        // Intentionally does not delete fixed reference data.
    }

    private function insertFixedReferenceData(): void
    {
        DB::transaction(function (): void {
            $this->upsertKnowledgeDomains();

            DB::unprepared(file_get_contents(database_path('sql/jornada_fixed_data_2026_08_legacy.sql')));

            $this->syncSequenceWithTable(
                sequence: 'TB_DOMINIO_CONHECIMENTO_PK_DOMINIO_CONHECIMENTO_seq',
                table: 'TB_DOMINIO_CONHECIMENTO',
                primaryKey: 'PK_DOMINIO_CONHECIMENTO',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_PERFIS_JORNADA_PK_PERFIS_JORNADA_seq',
                table: 'TB_PERFIS_JORNADA',
                primaryKey: 'PK_PERFIS_JORNADA',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_AREA_CONHECIMENTO_PK_AREA_CONHECIMENTO_seq',
                table: 'TB_AREA_CONHECIMENTO',
                primaryKey: 'PK_AREA_CONHECIMENTO',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_DIFICULDADE_PK_DIFICULDADE_seq',
                table: 'TB_DIFICULDADE',
                primaryKey: 'PK_DIFICULDADE',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_EXPERIENCIA_ATUACAO_PK_EXPERIENCIA_ATUACAO_seq',
                table: 'TB_EXPERIENCIA_ATUACAO',
                primaryKey: 'PK_EXPERIENCIA_ATUACAO',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_TIPO_PERFIL_PK_TIPO_PERFIL_seq',
                table: 'TB_TIPO_PERFIL',
                primaryKey: 'PK_TIPO_PERFIL',
            );
        });
    }

    private function ensureLegacyFixedReferenceTables(): void
    {
        if (! Schema::hasTable('TB_DIFICULDADE')) {
            Schema::create('TB_DIFICULDADE', function (Blueprint $table): void {
                $table->increments('PK_DIFICULDADE');
                $table->string('NO_DIFICULDADE_PT', 20)->nullable();
                $table->string('NO_DIFICULDADE_EN', 20)->nullable();
            });
        }

        if (! Schema::hasTable('TB_EXPERIENCIA_ATUACAO')) {
            Schema::create('TB_EXPERIENCIA_ATUACAO', function (Blueprint $table): void {
                $table->increments('PK_EXPERIENCIA_ATUACAO');
                $table->string('DS_EXPERIENCIA_ATUACAO_PT', 20)->nullable();
                $table->string('DS_EXPERIENCIA_ATUACAO_EN', 30)->nullable();
            });
        }

        if (! Schema::hasTable('TB_TIPO_PERFIL')) {
            Schema::create('TB_TIPO_PERFIL', function (Blueprint $table): void {
                $table->increments('PK_TIPO_PERFIL');
                $table->string('NO_TIPO_PERFIL', 20)->nullable();
            });
        }
    }

    private function upsertKnowledgeDomains(): void
    {
        foreach ([
            1 => ['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy'],
            2 => ['NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança da Informação', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Information Security'],
            3 => ['NO_DOMINIO_CONHECIMENTO_PT' => 'Conjunta (Privacidade/Segurança)', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Joint (Privacy/security)'],
        ] as $id => $attributes) {
            DB::table('TB_DOMINIO_CONHECIMENTO')->updateOrInsert(
                ['PK_DOMINIO_CONHECIMENTO' => $id],
                $attributes,
            );
        }

        $this->syncSequenceWithTable(
            sequence: 'TB_DOMINIO_CONHECIMENTO_PK_DOMINIO_CONHECIMENTO_seq',
            table: 'TB_DOMINIO_CONHECIMENTO',
            primaryKey: 'PK_DOMINIO_CONHECIMENTO',
        );
    }

    private function syncSequenceWithTable(string $sequence, string $table, string $primaryKey): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable($table)) {
            return;
        }

        DB::statement(sprintf(
            'SELECT setval(\'"%s"\', (SELECT COALESCE(MAX("%s"), 1) FROM "%s"), true)',
            $sequence,
            $primaryKey,
            $table,
        ));
    }
};
