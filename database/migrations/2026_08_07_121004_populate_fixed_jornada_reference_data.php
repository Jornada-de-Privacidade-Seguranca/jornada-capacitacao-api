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
