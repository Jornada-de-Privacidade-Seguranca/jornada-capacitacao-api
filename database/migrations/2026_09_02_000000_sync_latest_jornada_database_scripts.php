<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addLatestScriptColumns();
        $this->syncLatestReferenceData();
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
    }

    private function syncLatestReferenceData(): void
    {
        DB::transaction(function (): void {
            $this->updateKnowledgeAreaDescriptions();
            $this->updateCriteria();
            $this->updateSubcriterionCriteria();
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
        });
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

    private function updateCriteria(): void
    {
        if (! Schema::hasTable('TB_CRITERIOS')) {
            return;
        }

        DB::table('TB_CRITERIOS')
            ->where('NO_CRITERIO_PT', 'Competências')
            ->update(['NU_PESO_CRITERIO' => 45.92]);

        DB::table('TB_CRITERIOS')->updateOrInsert(
            ['NO_CRITERIO_PT' => 'Nenhum'],
            ['NO_CRITERIO_EN' => 'None', 'NU_PESO_CRITERIO' => 0],
        );
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
