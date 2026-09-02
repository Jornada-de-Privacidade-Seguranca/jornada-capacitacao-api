<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLatestScriptTables();
        $this->addLatestScriptColumns();
        $this->syncLatestReferenceData();
        $this->migrateLegacyJourneySelections();
        $this->addLatestForeignKeys();
    }

    public function down(): void
    {
        // Intentionally does not delete fixed reference data or schema columns.
    }

    private function addLatestScriptColumns(): void
    {
        if (Schema::hasTable('TB_AREA_CONHECIMENTO')) {
            Schema::table('TB_AREA_CONHECIMENTO', function (Blueprint $table): void {
                if (! Schema::hasColumn('TB_AREA_CONHECIMENTO', 'DS_AREA_CONHECIMENTO_PT')) {
                    $table->string('DS_AREA_CONHECIMENTO_PT', 500)->nullable();
                }

                if (! Schema::hasColumn('TB_AREA_CONHECIMENTO', 'DS_AREA_CONHECIMENTO_EN')) {
                    $table->string('DS_AREA_CONHECIMENTO_EN', 500)->nullable();
                }
            });
        }

        if (Schema::hasTable('TB_JORNADA') && ! Schema::hasColumn('TB_JORNADA', 'NU_PONTUACAO_TOTAL')) {
            Schema::table('TB_JORNADA', function (Blueprint $table): void {
                $table->integer('NU_PONTUACAO_TOTAL')->nullable();
            });
        }

        if (Schema::hasTable('TB_JORNADA') && ! Schema::hasColumn('TB_JORNADA', 'FK_SUBCRITERIOS_INGLES')) {
            Schema::table('TB_JORNADA', function (Blueprint $table): void {
                $table->unsignedInteger('FK_SUBCRITERIOS_INGLES')->nullable();
            });
        }

        if (Schema::hasTable('TB_CURSO_SUPERIOR') && ! Schema::hasColumn('TB_CURSO_SUPERIOR', 'FK_SUBCRITERIOS')) {
            Schema::table('TB_CURSO_SUPERIOR', function (Blueprint $table): void {
                $table->unsignedInteger('FK_SUBCRITERIOS')->nullable();
            });
        }

        if (
            Schema::hasTable('TB_CERTIFICACAO_PROFISSIONAL')
            && ! Schema::hasColumn('TB_CERTIFICACAO_PROFISSIONAL', 'NO_EMISSOR_CERTIFICACAO')
        ) {
            Schema::table('TB_CERTIFICACAO_PROFISSIONAL', function (Blueprint $table): void {
                $table->string('NO_EMISSOR_CERTIFICACAO', 100)->nullable();
            });
        }
    }

    private function syncLatestReferenceData(): void
    {
        DB::transaction(function (): void {
            $this->splitSeniorManagementJourneyProfile();
            $this->updateKnowledgeAreaDescriptions();
            $this->syncCriteria();
            $this->syncSubcriteria();
            $this->updateSubcriterionCriteria();
            $this->syncCompetencyCriterionWeights();
            $this->insertNoneProfessionalExperience();

            $this->syncSequenceWithTable(
                sequence: 'TB_CRITERIOS_PK_CRITERIOS_seq',
                table: 'TB_CRITERIOS',
                primaryKey: 'PK_CRITERIOS',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_EXPERIENCIA_ATUACAO_PK_EXPERIENCIA_ATUACAO_seq',
                table: 'TB_EXPERIENCIA_ATUACAO',
                primaryKey: 'PK_EXPERIENCIA_ATUACAO',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_SUBCRITERIOS_PK_SUBCRITERIOS_seq',
                table: 'TB_SUBCRITERIOS',
                primaryKey: 'PK_SUBCRITERIOS',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_PESO_CRIT_COMPETENCIA_PK_PESO_CRIT_COMPETENCIA_seq',
                table: 'TB_PESO_CRIT_COMPETENCIA',
                primaryKey: 'PK_PESO_CRIT_COMPETENCIA',
            );
            $this->syncSequenceWithTable(
                sequence: 'TB_CURSO_SUPERIOR_PK_CURSO_SUPERIOR_seq',
                table: 'TB_CURSO_SUPERIOR',
                primaryKey: 'PK_CURSO_SUPERIOR',
            );
        });
    }

    private function createLatestScriptTables(): void
    {
        if (! Schema::hasTable('TB_CRITERIOS')) {
            Schema::create('TB_CRITERIOS', function (Blueprint $table): void {
                $table->increments('PK_CRITERIOS');
                $table->string('NO_CRITERIO_PT', 100)->nullable();
                $table->string('NO_CRITERIO_EN', 100)->nullable();
                $table->double('NU_PESO_CRITERIO')->nullable();
            });
        }

        if (! Schema::hasTable('TB_SUBCRITERIOS')) {
            Schema::create('TB_SUBCRITERIOS', function (Blueprint $table): void {
                $table->increments('PK_SUBCRITERIOS');
                $table->unsignedInteger('FK_CRITERIOS')->nullable();
                $table->string('NO_SUBCRITERIO_PT', 100)->nullable();
                $table->string('NO_SUBCRITERIO_EN', 100)->nullable();
                $table->double('NU_PESO_SUBCRITERIO')->nullable();

                $table->foreign('FK_CRITERIOS')->references('PK_CRITERIOS')->on('TB_CRITERIOS');
            });
        }

        if (! Schema::hasTable('TB_PESO_CRIT_COMPETENCIA')) {
            Schema::create('TB_PESO_CRIT_COMPETENCIA', function (Blueprint $table): void {
                $table->increments('PK_PESO_CRIT_COMPETENCIA');
                $table->unsignedInteger('FK_PERFIS_JORNADA')->nullable();
                $table->unsignedInteger('FK_AREA_CONHECIMENTO')->nullable();
                $table->double('NU_PESO_CRIT_COMPETENCIA')->nullable();

                $table->foreign('FK_PERFIS_JORNADA')->references('PK_PERFIS_JORNADA')->on('TB_PERFIS_JORNADA');
                $table->foreign('FK_AREA_CONHECIMENTO')->references('PK_AREA_CONHECIMENTO')->on('TB_AREA_CONHECIMENTO');
            });
        }

        if (! Schema::hasTable('RL_CURSOS_SUPERIORES_JORNADA')) {
            Schema::create('RL_CURSOS_SUPERIORES_JORNADA', function (Blueprint $table): void {
                $table->increments('PK_CURSOS_SUPERIORES_JORNADA');
                $table->unsignedInteger('FK_JORNADA')->nullable();
                $table->unsignedInteger('FK_CURSO_SUPERIOR')->nullable();

                $table->foreign('FK_JORNADA')->references('PK_JORNADA')->on('TB_JORNADA');
                $table->foreign('FK_CURSO_SUPERIOR')->references('PK_CURSO_SUPERIOR')->on('TB_CURSO_SUPERIOR');
            });
        }
    }

    private function updateKnowledgeAreaDescriptions(): void
    {
        if (! Schema::hasTable('TB_AREA_CONHECIMENTO')) {
            return;
        }

        foreach ($this->knowledgeAreas() as $name => $descriptions) {
            DB::table('TB_AREA_CONHECIMENTO')
                ->where('NO_AREA_CONHECIMENTO_PT', $name)
                ->update($descriptions);
        }
    }

    private function syncCriteria(): void
    {
        if (! Schema::hasTable('TB_CRITERIOS')) {
            return;
        }

        foreach ($this->criteria() as $id => $criterion) {
            DB::table('TB_CRITERIOS')->updateOrInsert(
                ['PK_CRITERIOS' => $id],
                $criterion,
            );
        }
    }

    private function syncSubcriteria(): void
    {
        if (! Schema::hasTable('TB_SUBCRITERIOS')) {
            return;
        }

        foreach ($this->subcriteria() as $id => $subcriterion) {
            DB::table('TB_SUBCRITERIOS')->updateOrInsert(
                ['PK_SUBCRITERIOS' => $id],
                $subcriterion,
            );
        }
    }

    private function updateSubcriterionCriteria(): void
    {
        if (! Schema::hasTable('TB_SUBCRITERIOS')) {
            return;
        }

        foreach ($this->subcriterionCriteria() as $name => $criterionId) {
            DB::table('TB_SUBCRITERIOS')
                ->where('NO_SUBCRITERIO_PT', $name)
                ->update(['FK_CRITERIOS' => $criterionId]);
        }
    }

    private function insertNoneProfessionalExperience(): void
    {
        if (! Schema::hasTable('TB_EXPERIENCIA_ATUACAO')) {
            return;
        }

        DB::table('TB_EXPERIENCIA_ATUACAO')->updateOrInsert(
            ['DS_EXPERIENCIA_ATUACAO_PT' => 'Nenhum'],
            ['DS_EXPERIENCIA_ATUACAO_EN' => 'None'],
        );
    }

    private function migrateLegacyJourneySelections(): void
    {
        $this->migrateLegacyEnglishProficiencies();
        $this->migrateLegacyHigherEducationCourses();
        $this->migrateLegacyPostgraduateCourses();
    }

    private function migrateLegacyEnglishProficiencies(): void
    {
        if (
            ! Schema::hasTable('TB_PROFICIENCIA_INGLES')
            || ! Schema::hasTable('TB_SUBCRITERIOS')
            || ! Schema::hasTable('TB_JORNADA')
            || ! Schema::hasColumn('TB_JORNADA', 'FK_PROFICIENCIA_INGLES')
            || ! Schema::hasColumn('TB_JORNADA', 'FK_SUBCRITERIOS_INGLES')
        ) {
            return;
        }

        DB::table('TB_PROFICIENCIA_INGLES')
            ->select('PK_PROFICIENCIA_INGLES', 'NO_PROFICIENCIA_INGLES_PT', 'NO_PROFICIENCIA_INGLES_EN')
            ->orderBy('PK_PROFICIENCIA_INGLES')
            ->get()
            ->each(function (object $legacyProficiency): void {
                $subcriterionId = $this->findSubcriterionId(
                    $legacyProficiency->NO_PROFICIENCIA_INGLES_PT,
                    $legacyProficiency->NO_PROFICIENCIA_INGLES_EN,
                );

                if ($subcriterionId === null) {
                    return;
                }

                DB::table('TB_JORNADA')
                    ->where('FK_PROFICIENCIA_INGLES', $legacyProficiency->PK_PROFICIENCIA_INGLES)
                    ->whereNull('FK_SUBCRITERIOS_INGLES')
                    ->update(['FK_SUBCRITERIOS_INGLES' => $subcriterionId]);
            });
    }

    private function migrateLegacyHigherEducationCourses(): void
    {
        if (! Schema::hasTable('TB_CURSO_SUPERIOR') || ! Schema::hasColumn('TB_CURSO_SUPERIOR', 'FK_SUBCRITERIOS')) {
            return;
        }

        if (Schema::hasColumn('TB_CURSO_SUPERIOR', 'TP_CURSO_SUPERIOR')) {
            DB::table('TB_CURSO_SUPERIOR')
                ->select('PK_CURSO_SUPERIOR', 'TP_CURSO_SUPERIOR')
                ->whereNull('FK_SUBCRITERIOS')
                ->orderBy('PK_CURSO_SUPERIOR')
                ->get()
                ->each(function (object $course): void {
                    DB::table('TB_CURSO_SUPERIOR')
                        ->where('PK_CURSO_SUPERIOR', $course->PK_CURSO_SUPERIOR)
                        ->update([
                            'FK_SUBCRITERIOS' => $this->findEducationSubcriterionId($course->TP_CURSO_SUPERIOR) ?? 1,
                        ]);
                });
        }

        if (
            ! Schema::hasTable('RL_CURSOS_SUPERIORES_JORNADA')
            || ! Schema::hasTable('TB_JORNADA')
            || ! Schema::hasColumn('TB_JORNADA', 'FK_CURSO_SUPERIOR')
        ) {
            return;
        }

        DB::table('TB_JORNADA')
            ->select('PK_JORNADA', 'FK_CURSO_SUPERIOR')
            ->whereNotNull('FK_CURSO_SUPERIOR')
            ->orderBy('PK_JORNADA')
            ->get()
            ->each(function (object $journey): void {
                $this->attachHigherEducationCourse(
                    journeyId: $journey->PK_JORNADA,
                    courseId: $journey->FK_CURSO_SUPERIOR,
                );
            });
    }

    private function migrateLegacyPostgraduateCourses(): void
    {
        if (
            ! Schema::hasTable('TB_POS_GRADUACAO')
            || ! Schema::hasTable('TB_CURSO_SUPERIOR')
            || ! Schema::hasTable('TB_JORNADA')
            || ! Schema::hasColumn('TB_JORNADA', 'FK_POS_GRADUACAO')
        ) {
            return;
        }

        DB::table('TB_JORNADA')
            ->join('TB_POS_GRADUACAO', 'TB_POS_GRADUACAO.PK_POS_GRADUACAO', '=', 'TB_JORNADA.FK_POS_GRADUACAO')
            ->select(
                'TB_JORNADA.PK_JORNADA',
                'TB_POS_GRADUACAO.NO_POS_GRADUACAO_PT',
                'TB_POS_GRADUACAO.NO_POS_GRADUACAO_EN',
                'TB_POS_GRADUACAO.TP_POS_GRADUACAO',
            )
            ->orderBy('TB_JORNADA.PK_JORNADA')
            ->get()
            ->each(function (object $journey): void {
                $courseId = $this->upsertCourseFromLegacyPostgraduate($journey);
                $this->attachHigherEducationCourse(
                    journeyId: $journey->PK_JORNADA,
                    courseId: $courseId,
                );
            });
    }

    private function syncCompetencyCriterionWeights(): void
    {
        if (
            ! Schema::hasTable('TB_PESO_CRIT_COMPETENCIA')
            || DB::table('TB_PESO_CRIT_COMPETENCIA')->exists()
        ) {
            return;
        }

        $currentProfileName = null;
        $section = $this->fixedDataSection(
            startMarker: '-- Insert TB_PESO_CRIT_COMPETENCIA',
            endMarker: '-- Insert TB_EXPERIENCIA_ATUACAO',
        );

        foreach (preg_split('/\R/', $section) as $line) {
            if (preg_match('/^--\s*(.+)$/', trim($line), $matches) === 1) {
                $currentProfileName = trim($matches[1]);
                continue;
            }

            if (
                $currentProfileName === null
                || preg_match(
                    '/VALUES\s*\(\s*\d+\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\);/i',
                    $line,
                    $matches,
                ) !== 1
            ) {
                continue;
            }

            $profileId = $this->findJourneyProfileId($currentProfileName);

            if ($profileId === null) {
                continue;
            }

            DB::table('TB_PESO_CRIT_COMPETENCIA')->insert([
                'FK_PERFIS_JORNADA' => $profileId,
                'FK_AREA_CONHECIMENTO' => (int) $matches[1],
                'NU_PESO_CRIT_COMPETENCIA' => (float) $matches[2],
            ]);
        }
    }

    private function splitSeniorManagementJourneyProfile(): void
    {
        if (! Schema::hasTable('TB_PERFIS_JORNADA')) {
            return;
        }

        DB::table('TB_PERFIS_JORNADA')
            ->where('NO_PERFIS_PT', 'Alta Administração')
            ->update([
                'NO_PERFIS_PT' => 'Alta Administração 1',
                'NO_PERFIS_EN' => 'Senior Management 1',
            ]);

        if (! DB::table('TB_PERFIS_JORNADA')->where('NO_PERFIS_PT', 'Alta Administração 2')->exists()) {
            DB::table('TB_PERFIS_JORNADA')->insert([
                'FK_DOMINIO_CONHECIMENTO' => 3,
                'NO_PERFIS_PT' => 'Alta Administração 2',
                'NO_PERFIS_EN' => 'Senior Management 2',
                'DS_PERFIS_PT' => 'Entende-se que a Alta Administração 2 em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica.',
                'DS_PERFIS_EN' => 'Senior management 2 in public sector bodies comprises individuals in leadership and strategic decision-making positions.',
            ]);
        }
    }

    private function findJourneyProfileId(string $profileName): ?int
    {
        $profileId = DB::table('TB_PERFIS_JORNADA')
            ->where('NO_PERFIS_PT', $profileName)
            ->value('PK_PERFIS_JORNADA');

        return $profileId === null ? null : (int) $profileId;
    }

    private function findSubcriterionId(?string $namePt, ?string $nameEn): ?int
    {
        $query = DB::table('TB_SUBCRITERIOS');

        if ($namePt !== null && $nameEn !== null) {
            $query->where(function ($query) use ($namePt, $nameEn): void {
                $query
                    ->where('NO_SUBCRITERIO_PT', $namePt)
                    ->orWhere('NO_SUBCRITERIO_EN', $nameEn);
            });
        } elseif ($namePt !== null) {
            $query->where('NO_SUBCRITERIO_PT', $namePt);
        } elseif ($nameEn !== null) {
            $query->where('NO_SUBCRITERIO_EN', $nameEn);
        } else {
            return null;
        }

        $subcriterionId = $query->value('PK_SUBCRITERIOS');

        return $subcriterionId === null ? null : (int) $subcriterionId;
    }

    private function findEducationSubcriterionId(?int $legacyType): ?int
    {
        $name = match ($legacyType) {
            2 => 'Pós-Graduação',
            3 => 'Mestrado',
            4 => 'Doutorado',
            default => 'Graduação',
        };

        return $this->findSubcriterionId($name, null);
    }

    private function findPostgraduateSubcriterionId(?int $legacyType): ?int
    {
        $name = match ($legacyType) {
            3 => 'Mestrado',
            4 => 'Doutorado',
            default => 'Pós-Graduação',
        };

        return $this->findSubcriterionId($name, null);
    }

    private function upsertCourseFromLegacyPostgraduate(object $postgraduate): int
    {
        $existingCourseId = DB::table('TB_CURSO_SUPERIOR')
            ->where('NO_CURSO_SUPERIOR_PT', $postgraduate->NO_POS_GRADUACAO_PT)
            ->where('NO_CURSO_SUPERIOR_EN', $postgraduate->NO_POS_GRADUACAO_EN)
            ->value('PK_CURSO_SUPERIOR');

        if ($existingCourseId !== null) {
            return (int) $existingCourseId;
        }

        return (int) DB::table('TB_CURSO_SUPERIOR')->insertGetId([
            'FK_SUBCRITERIOS' => $this->findPostgraduateSubcriterionId($postgraduate->TP_POS_GRADUACAO) ?? 2,
            'NO_CURSO_SUPERIOR_PT' => $postgraduate->NO_POS_GRADUACAO_PT,
            'NO_CURSO_SUPERIOR_EN' => $postgraduate->NO_POS_GRADUACAO_EN,
        ]);
    }

    private function attachHigherEducationCourse(int $journeyId, int $courseId): void
    {
        if (! Schema::hasTable('RL_CURSOS_SUPERIORES_JORNADA')) {
            return;
        }

        DB::table('RL_CURSOS_SUPERIORES_JORNADA')->updateOrInsert([
            'FK_JORNADA' => $journeyId,
            'FK_CURSO_SUPERIOR' => $courseId,
        ]);
    }

    private function addLatestForeignKeys(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->addForeignKeyIfMissing(
            table: 'TB_CURSO_SUPERIOR',
            constraint: 'tb_curso_superior_fk_subcriterios_foreign',
            column: 'FK_SUBCRITERIOS',
            referencedTable: 'TB_SUBCRITERIOS',
            referencedColumn: 'PK_SUBCRITERIOS',
        );
        $this->addForeignKeyIfMissing(
            table: 'TB_JORNADA',
            constraint: 'tb_jornada_fk_subcriterios_ingles_foreign',
            column: 'FK_SUBCRITERIOS_INGLES',
            referencedTable: 'TB_SUBCRITERIOS',
            referencedColumn: 'PK_SUBCRITERIOS',
        );
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referencedTable,
        string $referencedColumn,
    ): void {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($referencedTable)
            || ! Schema::hasColumn($table, $column)
            || $this->foreignKeyExists($constraint)
        ) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE "%s" ADD CONSTRAINT "%s" FOREIGN KEY ("%s") REFERENCES "%s" ("%s")',
            $table,
            $constraint,
            $column,
            $referencedTable,
            $referencedColumn,
        ));
    }

    private function foreignKeyExists(string $constraint): bool
    {
        return DB::table('pg_constraint')
            ->where('conname', $constraint)
            ->exists();
    }

    /**
     * @return array<string, array{DS_AREA_CONHECIMENTO_PT: string, DS_AREA_CONHECIMENTO_EN: string}>
     */
    private function knowledgeAreas(): array
    {
        return [
            'Gestão de Privacidade' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a legislações e obrigações legais referentes a privacidade de dados.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge of legislation and legal obligations regarding data privacy.',
            ],
            'Estratégias de Privacidade' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a boas práticas, estratégias de implementação e técnicas de privacidade de dados.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge of best practices, implementation strategies, and data privacy techniques.',
            ],
            'Documentos Orientativos' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a documentações oficiais.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to official documentation.',
            ],
            'Gestão de Riscos e Conformidade' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a gestão de riscos de privacidade e conformidade com regulamentações, auditorias e fiscalizações.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to privacy risk management and compliance with regulations, audits, and inspections.',
            ],
            'Conhecimentos em Tecnologia da Informação' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a tecnologia da informação, como programação, armazenamento de arquivos, etc.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to information technology, such as programming, file storage, etc.',
            ],
            'Privacy By Design' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados aos conceitos e aplicações de privacy by design e privacy by default.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge regarding the concepts and applications of privacy by design and privacy by default.',
            ],
            'Gestão de Segurança da Informação' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a legislações e obrigações legais referentes a segurança da informação.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to legislation and legal obligations relating to information security.',
            ],
            'Gestão de Riscos' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao tratamento e gestão de riscos.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to risk treatment and management.',
            ],
            'Gestão do Uso dos Recursos Operacionais e de Comunicações' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados a gestão do uso de mídias sociais, dispositivos móveis e serviços em nuvem.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to the management of social media usage, mobile devices, and cloud services.',
            ],
            'Tratamento da Informação' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao tratamento de informação classificada, restrição de acesso, recursos criptográficos, etc.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to the handling of classified information, access restrictions, cryptographic resources, etc.',
            ],
            'Gestão de Ativos' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao mapeamento e tratamento de ativos.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to asset mapping and treatment.',
            ],
            'Gestão de Incidentes Cibernéticos' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao tratamento, prevenção e respostas à incidentes ciberneticos, atividades e responsabilidades da da Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos (ETIR).',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge regarding the handling, prevention of, and response to cyber incidents, as well as the activities and responsibilities of the Cyber ​​Incident Prevention, Handling, and Response Team (ETIR).',
            ],
            'Gestão de Continuidade' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao processo de continuidade de negócios em segurança da informação.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to the information security business continuity process.',
            ],
            'Segurança de Controle de Acesso' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao controle de acesso, privilégios mínimos, senhas, autenticação, etc.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to access control, least privilege, passwords, authentication, etc.',
            ],
            'Security By Design' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados aos conceitos e aplicações de security by design.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge regarding the concepts and applications of security by design.',
            ],
            'Gestão de Mudança' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao processo da gestão e tipos de classificação sobre a mudança nos aspectos de segurança da informação.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge regarding the management process and classification types concerning changes in information security aspects.',
            ],
            'Gestão de Conformidade' => [
                'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao processo de avaliação de conformidade nos aspectos da segurança da informação.',
                'DS_AREA_CONHECIMENTO_EN' => 'Knowledge related to the conformity assessment process regarding information security aspects.',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function subcriterionCriteria(): array
    {
        return [
            'Graduação' => 2,
            'Pós-Graduação' => 2,
            'Mestrado' => 2,
            'Doutorado' => 2,
            'Tempo de Experiência Profissional' => 1,
            'Tempo de Atuação no Perfil' => 1,
            'Básico - Leitura' => 4,
            'Intermediário - Leitura e Escrita' => 4,
            'Avançado - Fluente' => 4,
        ];
    }

    /**
     * @return array<int, array{NO_CRITERIO_PT: string, NO_CRITERIO_EN: string, NU_PESO_CRITERIO: float|int}>
     */
    private function criteria(): array
    {
        return [
            1 => ['NO_CRITERIO_PT' => 'Experiência Profissional', 'NO_CRITERIO_EN' => 'Professional Experience', 'NU_PESO_CRITERIO' => 23.81],
            2 => ['NO_CRITERIO_PT' => 'Formação Acadêmica', 'NO_CRITERIO_EN' => 'Education', 'NU_PESO_CRITERIO' => 14.7],
            3 => ['NO_CRITERIO_PT' => 'Certificação Profissional', 'NO_CRITERIO_EN' => 'Professional Certification', 'NU_PESO_CRITERIO' => 10.72],
            4 => ['NO_CRITERIO_PT' => 'Nível de Proficiência - Inglês', 'NO_CRITERIO_EN' => 'Proficiency Level - English', 'NU_PESO_CRITERIO' => 4.85],
            5 => ['NO_CRITERIO_PT' => 'Competências', 'NO_CRITERIO_EN' => 'Competencies', 'NU_PESO_CRITERIO' => 45.92],
            6 => ['NO_CRITERIO_PT' => 'Nenhum', 'NO_CRITERIO_EN' => 'None', 'NU_PESO_CRITERIO' => 0],
        ];
    }

    /**
     * @return array<int, array{FK_CRITERIOS: int, NO_SUBCRITERIO_PT: string, NO_SUBCRITERIO_EN: string, NU_PESO_SUBCRITERIO: float|int}>
     */
    private function subcriteria(): array
    {
        return [
            1 => ['FK_CRITERIOS' => 2, 'NO_SUBCRITERIO_PT' => 'Graduação', 'NO_SUBCRITERIO_EN' => 'Graduation', 'NU_PESO_SUBCRITERIO' => 8.829],
            2 => ['FK_CRITERIOS' => 2, 'NO_SUBCRITERIO_PT' => 'Pós-Graduação', 'NO_SUBCRITERIO_EN' => 'Postgraduate', 'NU_PESO_SUBCRITERIO' => 15.751],
            3 => ['FK_CRITERIOS' => 2, 'NO_SUBCRITERIO_PT' => 'Mestrado', 'NO_SUBCRITERIO_EN' => 'Masters', 'NU_PESO_SUBCRITERIO' => 27.18],
            4 => ['FK_CRITERIOS' => 2, 'NO_SUBCRITERIO_PT' => 'Doutorado', 'NO_SUBCRITERIO_EN' => 'Doctorate', 'NU_PESO_SUBCRITERIO' => 48.241],
            5 => ['FK_CRITERIOS' => 1, 'NO_SUBCRITERIO_PT' => 'Tempo de Experiência Profissional', 'NO_SUBCRITERIO_EN' => 'Years of Professional Experience', 'NU_PESO_SUBCRITERIO' => 50],
            6 => ['FK_CRITERIOS' => 1, 'NO_SUBCRITERIO_PT' => 'Tempo de Atuação no Perfil', 'NO_SUBCRITERIO_EN' => 'Time Spent on Role', 'NU_PESO_SUBCRITERIO' => 50],
            7 => ['FK_CRITERIOS' => 4, 'NO_SUBCRITERIO_PT' => 'Básico - Leitura', 'NO_SUBCRITERIO_EN' => 'Basic - Reading', 'NU_PESO_SUBCRITERIO' => 10.616],
            8 => ['FK_CRITERIOS' => 4, 'NO_SUBCRITERIO_PT' => 'Intermediário - Leitura e Escrita', 'NO_SUBCRITERIO_EN' => 'Intermediary - Reading and Writing', 'NU_PESO_SUBCRITERIO' => 26.05],
            9 => ['FK_CRITERIOS' => 4, 'NO_SUBCRITERIO_PT' => 'Avançado - Fluente', 'NO_SUBCRITERIO_EN' => 'Advanced - Fluent', 'NU_PESO_SUBCRITERIO' => 63.335],
        ];
    }

    private function fixedDataSection(string $startMarker, string $endMarker): string
    {
        $sql = file_get_contents(database_path('sql/jornada_fixed_data.sql'));
        $start = strpos($sql, $startMarker);
        $end = strpos($sql, $endMarker);

        if ($start === false || $end === false || $end <= $start) {
            return '';
        }

        return substr($sql, $start, $end - $start);
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
