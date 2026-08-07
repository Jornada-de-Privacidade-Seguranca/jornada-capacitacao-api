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

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE "TB_PROFICIENCIA_INGLES" ALTER COLUMN "NO_PROFICIENCIA_INGLES_PT" TYPE VARCHAR(40)');
            DB::statement('ALTER TABLE "TB_PROFICIENCIA_INGLES" ALTER COLUMN "NO_PROFICIENCIA_INGLES_EN" TYPE VARCHAR(40)');
            DB::statement('ALTER TABLE "TB_EXPERIENCIA_ATUACAO" ALTER COLUMN "DS_EXPERIENCIA_ATUACAO_EN" TYPE VARCHAR(30)');
        }

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
            $this->restartSequence('TB_PERFIS_JORNADA_PK_PERFIS_JORNADA_seq', 1, false);

            $sql = file_get_contents(database_path('sql/jornada_fixed_data.sql'));
            $sql = preg_replace('/^INSERT INTO "TB_DOMINIO_CONHECIMENTO".*;\R?/m', '', $sql);

            DB::unprepared($sql);

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

    private function restartSequence(string $sequence, int $value, bool $isCalled): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf(
            'SELECT setval(\'"%s"\', %d, %s)',
            $sequence,
            $value,
            $isCalled ? 'true' : 'false',
        ));
    }

    private function syncSequenceWithTable(string $sequence, string $table, string $primaryKey): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
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
