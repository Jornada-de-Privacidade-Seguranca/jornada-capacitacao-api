<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\CompetencyCriterionWeight;
use App\Models\Criterion;
use App\Models\Difficulty;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\ProfileType;
use App\Models\Subcriterion;
use App\Models\Usuario;
use Database\Seeders\ManualSwaggerDashboardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JornadaSchemaTest extends TestCase
{
    use RefreshDatabase;

    private const SENIOR_MANAGEMENT_1_DESCRIPTION_PT = 'Entende-se que a Alta Administração em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica que representam a administração pública e têm a responsabilidade de orientar a política de privacidade e proteção de dados. Isso inclui:
1.Ministros de Estado e Secretários, Presidentes, Vice-Presidente, Reitores, Vice-Reitores, Pró-Reitores e Comandantes das Forças Armadas, Comitê de Segurança da Informação.';

    private const SENIOR_MANAGEMENT_2_DESCRIPTION_PT = 'Entende-se que a Alta Administração 2 em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica que representam a administração pública e têm a responsabilidade de orientar a política de privacidade e proteção de dados. Isso inclui:
1.Diretores, Vice-Diretores, Superintendentes, Subsecretários, Coordenadores-Gerais.
2.Chefes de Gabinete, Subchefes e Assessores.
3.Assessor de Controle Interno, Ouvidor. ';

    public function test_it_creates_jornada_tables_from_the_database_modeling(): void
    {
        foreach ([
            'TB_DOMINIO_CONHECIMENTO',
            'TB_PERFIS_JORNADA',
            'TB_AREA_CONHECIMENTO',
            'TB_CRITERIOS',
            'TB_SUBCRITERIOS',
            'TB_PESO_CRIT_COMPETENCIA',
            'TB_DIFICULDADE',
            'TB_COMPETENCIAS',
            'RL_PERFIS_COMPETENCIAS',
            'TB_CURSO_SUPERIOR',
            'TB_CERTIFICACAO_PROFISSIONAL',
            'TB_EXPERIENCIA_ATUACAO',
            'TB_TIPO_PERFIL',
            'TB_USUARIO',
            'TB_JORNADA',
            'TB_CONHECIMENTOS_ADQUIRIDOS',
            'RL_CERTIFICACAO_JORNADA',
            'RL_CURSOS_SUPERIORES_JORNADA',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} was not created.");
        }

        $this->assertTrue(Schema::hasColumn('TB_AREA_CONHECIMENTO', 'DS_AREA_CONHECIMENTO_PT'));
        $this->assertTrue(Schema::hasColumn('TB_AREA_CONHECIMENTO', 'DS_AREA_CONHECIMENTO_EN'));
        $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'NU_PONTUACAO_TOTAL'));
    }

    public function test_it_migrates_fixed_jornada_reference_data(): void
    {
        $this->assertDatabaseCount('TB_DOMINIO_CONHECIMENTO', 3);
        $this->assertDatabaseCount('TB_PERFIS_JORNADA', 21);
        $this->assertDatabaseCount('TB_AREA_CONHECIMENTO', 17);
        $this->assertDatabaseCount('TB_DIFICULDADE', 5);
        $this->assertDatabaseCount('TB_CRITERIOS', 6);
        $this->assertDatabaseCount('TB_SUBCRITERIOS', 10);
        $this->assertDatabaseCount('TB_PESO_CRIT_COMPETENCIA', 270);
        $this->assertDatabaseCount('TB_EXPERIENCIA_ATUACAO', 6);
        $this->assertDatabaseCount('TB_TIPO_PERFIL', 3);

        $this->assertDatabaseHas('TB_DOMINIO_CONHECIMENTO', [
            'PK_DOMINIO_CONHECIMENTO' => 1,
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
            'PK_PERFIS_JORNADA' => 1,
            'FK_DOMINIO_CONHECIMENTO' => 1,
            'NO_PERFIS_PT' => 'Encarregado pelo Tratamento de Dados Pessoais',
            'NO_PERFIS_EN' => 'Data Protection Officer',
        ]);
        $this->assertDatabaseHas('TB_AREA_CONHECIMENTO', [
            'PK_AREA_CONHECIMENTO' => 17,
            'NO_AREA_CONHECIMENTO_PT' => 'Gestão de Conformidade',
            'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos relacionados ao processo de avaliação de conformidade nos aspectos da segurança da informação.',
        ]);
        $this->assertDatabaseHas('TB_DIFICULDADE', [
            'PK_DIFICULDADE' => 5,
            'NO_DIFICULDADE_PT' => 'Especialista',
        ]);
        $this->assertDatabaseHas('TB_CRITERIOS', [
            'PK_CRITERIOS' => 5,
            'NO_CRITERIO_PT' => 'Competências',
            'NU_PESO_CRITERIO' => 45.92,
        ]);
        $this->assertDatabaseHas('TB_CRITERIOS', [
            'PK_CRITERIOS' => 6,
            'NO_CRITERIO_PT' => 'Nenhum',
            'NO_CRITERIO_EN' => 'None',
            'NU_PESO_CRITERIO' => 0,
        ]);
        $this->assertDatabaseHas('TB_SUBCRITERIOS', [
            'PK_SUBCRITERIOS' => 9,
            'FK_CRITERIOS' => 4,
            'NO_SUBCRITERIO_PT' => 'Avançado - Fluente',
            'NU_PESO_SUBCRITERIO' => 63.335,
        ]);
        $this->assertDatabaseHas('TB_SUBCRITERIOS', [
            'PK_SUBCRITERIOS' => 10,
            'FK_CRITERIOS' => 4,
            'NO_SUBCRITERIO_PT' => 'Nenhum',
            'NO_SUBCRITERIO_EN' => 'None',
            'NU_PESO_SUBCRITERIO' => 0,
        ]);
        $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
            'PK_PESO_CRIT_COMPETENCIA' => 270,
            'FK_PERFIS_JORNADA' => 5,
            'FK_AREA_CONHECIMENTO' => 4,
            'NU_PESO_CRIT_COMPETENCIA' => 16,
        ]);
        $this->assertDatabaseHas('TB_EXPERIENCIA_ATUACAO', [
            'PK_EXPERIENCIA_ATUACAO' => 5,
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Mais de 10 anos',
        ]);
        $this->assertDatabaseHas('TB_EXPERIENCIA_ATUACAO', [
            'PK_EXPERIENCIA_ATUACAO' => 6,
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Nenhum',
            'DS_EXPERIENCIA_ATUACAO_EN' => 'None',
        ]);
        $this->assertDatabaseHas('TB_TIPO_PERFIL', [
            'PK_TIPO_PERFIL' => 3,
            'NO_TIPO_PERFIL' => 'Usuário',
        ]);
    }

    public function test_manual_swagger_dashboard_seeder_uses_latest_jornada_schema(): void
    {
        $this->seed(ManualSwaggerDashboardSeeder::class);

        $userId = Usuario::query()->where('DS_EMAIL', 'user@jornada.local')->value('PK_USUARIO');
        $journeyProfileId = JourneyProfile::query()
            ->where('NO_PERFIS_PT', 'Encarregado pelo Tratamento de Dados Pessoais')
            ->value('PK_PERFIS_JORNADA');
        $englishSubcriterionId = Subcriterion::query()
            ->where('NO_SUBCRITERIO_PT', 'Intermediário - Leitura e Escrita')
            ->value('PK_SUBCRITERIOS');

        $this->assertDatabaseHas('TB_JORNADA', [
            'FK_USUARIO' => $userId,
            'FK_PERFIS_JORNADA' => $journeyProfileId,
            'FK_SUBCRITERIOS_INGLES' => $englishSubcriterionId,
            'DH_CRIADO_EM' => '2026-07-23 09:00:00',
            'DH_FIM' => '2026-07-23 10:00:00',
        ]);
        $this->assertDatabaseHas('TB_JORNADA', [
            'FK_USUARIO' => $userId,
            'FK_PERFIS_JORNADA' => $journeyProfileId,
            'DH_CRIADO_EM' => '2026-07-24 09:00:00',
            'DH_FIM' => null,
        ]);
        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'FK_SUBCRITERIOS' => 1,
        ]);
        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Privacidade e Protecao de Dados',
            'FK_SUBCRITERIOS' => 2,
        ]);

        $completedJourneyId = Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNotNull('DH_FIM')
            ->value('PK_JORNADA');
        $courseIds = HigherEducationCourse::query()
            ->whereIn('NO_CURSO_SUPERIOR_PT', ['Direito', 'Privacidade e Protecao de Dados'])
            ->pluck('PK_CURSO_SUPERIOR');

        foreach ($courseIds as $courseId) {
            $this->assertDatabaseHas('RL_CURSOS_SUPERIORES_JORNADA', [
                'FK_JORNADA' => $completedJourneyId,
                'FK_CURSO_SUPERIOR' => $courseId,
            ]);
        }
    }

    public function test_it_runs_2026_08_fixed_data_migration_on_empty_legacy_schema_before_latest_upgrade(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $database = storage_path('framework/testing/legacy_2026_08_upgrade.sqlite');

        if (! is_dir(dirname($database))) {
            mkdir(dirname($database), 0777, true);
        }

        @unlink($database);
        touch($database);

        config()->set('database.connections.legacy_2026_08_upgrade', [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('legacy_2026_08_upgrade');
        DB::setDefaultConnection('legacy_2026_08_upgrade');

        try {
            $this->createLegacyJornadaSchema();

            $fixedDataMigration = require database_path('migrations/2026_08_07_121004_populate_fixed_jornada_reference_data.php');
            $fixedDataMigration->up();

            $this->assertFalse(Schema::hasTable('TB_CRITERIOS'));
            $this->assertFalse(Schema::hasTable('TB_SUBCRITERIOS'));
            $this->assertFalse(Schema::hasTable('TB_PESO_CRIT_COMPETENCIA'));
            $this->assertDatabaseCount('TB_PERFIS_JORNADA', 20);
            $this->assertDatabaseCount('TB_AREA_CONHECIMENTO', 17);
            $this->assertDatabaseCount('TB_DIFICULDADE', 5);
            $this->assertDatabaseCount('TB_EXPERIENCIA_ATUACAO', 6);
            $this->assertDatabaseCount('TB_TIPO_PERFIL', 3);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'PK_PERFIS_JORNADA' => 1,
                'NO_PERFIS_PT' => 'Encarregado pelo Tratamento de Dados Pessoais',
            ]);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'PK_PERFIS_JORNADA' => 18,
                'NO_PERFIS_PT' => 'Alta Administração',
            ]);

            $latestScriptMigration = require database_path('migrations/2026_09_02_000000_sync_latest_jornada_database_scripts.php');
            $latestScriptMigration->up();

            $this->assertDatabaseCount('TB_PERFIS_JORNADA', 21);
            $this->assertDatabaseCount('TB_CRITERIOS', 6);
            $this->assertDatabaseCount('TB_SUBCRITERIOS', 10);
            $this->assertDatabaseCount('TB_PESO_CRIT_COMPETENCIA', 270);
            $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
                'FK_PERFIS_JORNADA' => 1,
                'FK_AREA_CONHECIMENTO' => 5,
                'NU_PESO_CRIT_COMPETENCIA' => 8,
            ]);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'PK_PERFIS_JORNADA' => 18,
                'NO_PERFIS_PT' => 'Alta Administração 1',
                'DS_PERFIS_PT' => self::SENIOR_MANAGEMENT_1_DESCRIPTION_PT,
            ]);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'NO_PERFIS_PT' => 'Alta Administração 2',
                'DS_PERFIS_PT' => self::SENIOR_MANAGEMENT_2_DESCRIPTION_PT,
            ]);
        } finally {
            DB::disconnect('legacy_2026_08_upgrade');
            DB::setDefaultConnection($defaultConnection);
            DB::purge('legacy_2026_08_upgrade');
            @unlink($database);
        }
    }

    public function test_it_upgrades_legacy_jornada_schema_to_latest_database_scripts(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $database = storage_path('framework/testing/legacy_upgrade.sqlite');

        if (! is_dir(dirname($database))) {
            mkdir(dirname($database), 0777, true);
        }

        @unlink($database);
        touch($database);

        config()->set('database.connections.legacy_upgrade', [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('legacy_upgrade');
        DB::setDefaultConnection('legacy_upgrade');

        try {
            $this->createLegacyJornadaSchema();
            $this->seedLegacyJornadaSchema();

            $migration = require database_path('migrations/2026_09_02_000000_sync_latest_jornada_database_scripts.php');
            $migration->up();

            foreach ([
                'TB_CRITERIOS',
                'TB_SUBCRITERIOS',
                'TB_PESO_CRIT_COMPETENCIA',
                'RL_CURSOS_SUPERIORES_JORNADA',
            ] as $table) {
                $this->assertTrue(Schema::hasTable($table), "Table {$table} was not created during upgrade.");
            }

            $this->assertTrue(Schema::hasColumn('TB_CURSO_SUPERIOR', 'FK_SUBCRITERIOS'));
            $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'FK_SUBCRITERIOS_INGLES'));
            $this->assertTrue(Schema::hasColumn('TB_CERTIFICACAO_PROFISSIONAL', 'NO_EMISSOR_CERTIFICACAO'));
            $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'NU_PONTUACAO_TOTAL'));
            $this->assertDatabaseCount('TB_CRITERIOS', 6);
            $this->assertDatabaseCount('TB_SUBCRITERIOS', 10);
            $this->assertDatabaseCount('TB_PERFIS_JORNADA', 21);
            $this->assertDatabaseCount('TB_PESO_CRIT_COMPETENCIA', 270);
            $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
                'FK_PERFIS_JORNADA' => 1,
                'FK_AREA_CONHECIMENTO' => 5,
                'NU_PESO_CRIT_COMPETENCIA' => 8,
            ]);
            $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
                'FK_PERFIS_JORNADA' => 14,
                'FK_AREA_CONHECIMENTO' => 5,
                'NU_PESO_CRIT_COMPETENCIA' => 18.92,
            ]);
            $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
                'FK_PERFIS_JORNADA' => 17,
                'FK_AREA_CONHECIMENTO' => 5,
                'NU_PESO_CRIT_COMPETENCIA' => 10.305,
            ]);
            $this->assertDatabaseHas('TB_PESO_CRIT_COMPETENCIA', [
                'FK_PERFIS_JORNADA' => 20,
                'FK_AREA_CONHECIMENTO' => 5,
                'NU_PESO_CRIT_COMPETENCIA' => 8.25,
            ]);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'PK_PERFIS_JORNADA' => 18,
                'NO_PERFIS_PT' => 'Alta Administração 1',
                'DS_PERFIS_PT' => self::SENIOR_MANAGEMENT_1_DESCRIPTION_PT,
            ]);
            $this->assertDatabaseHas('TB_PERFIS_JORNADA', [
                'NO_PERFIS_PT' => 'Alta Administração 2',
                'NO_PERFIS_EN' => 'Senior Management 2',
                'DS_PERFIS_PT' => self::SENIOR_MANAGEMENT_2_DESCRIPTION_PT,
            ]);
            $this->assertDatabaseHas('TB_JORNADA', [
                'PK_JORNADA' => 1,
                'FK_SUBCRITERIOS_INGLES' => 8,
            ]);
            $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
                'PK_CURSO_SUPERIOR' => 1,
                'FK_SUBCRITERIOS' => 1,
            ]);
            $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
                'NO_CURSO_SUPERIOR_PT' => 'Governança de Privacidade',
                'FK_SUBCRITERIOS' => 3,
            ]);
            $this->assertDatabaseCount('RL_CURSOS_SUPERIORES_JORNADA', 2);
        } finally {
            DB::disconnect('legacy_upgrade');
            DB::setDefaultConnection($defaultConnection);
            DB::purge('legacy_upgrade');
            @unlink($database);
        }
    }

    public function test_it_persists_journey_relationships_with_script_table_names(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        $user = Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria@example.com',
            'DS_SENHA' => 'secret',
            'ST_ATIVO' => 1,
        ]);
        $knowledgeDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $journeyProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Privacidade',
            'DS_PERFIS_PT' => 'Privacidade',
        ]);
        $knowledgeArea = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Governanca']);
        $difficulty = Difficulty::create(['NO_DIFICULDADE_PT' => 'Basico']);
        $competency = Competency::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'FK_AREA_CONHECIMENTO' => $knowledgeArea->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_COMPETENCIAS_PT' => 'LGPD',
        ]);
        $journeyProfile->competencies()->attach($competency->PK_COMPETENCIAS);

        $experience = ProfessionalExperience::create(['DS_EXPERIENCIA_ATUACAO_PT' => '1 ano']);
        $criterion = Criterion::create(['NO_CRITERIO_PT' => 'Formação Acadêmica']);
        $subcriterion = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Basico',
        ]);
        $higherEducation = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $subcriterion->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
        ]);

        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_SUBCRITERIOS_INGLES' => $subcriterion->PK_SUBCRITERIOS,
            'NU_PONTUACAO_TOTAL' => 85,
        ]);
        $journey->higherEducationCourses()->attach($higherEducation->PK_CURSO_SUPERIOR);

        AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
        ]);

        $certification = ProfessionalCertification::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_CERTIFICACAO_PROFISSIONAL' => 'CISSP',
        ]);
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);
        CompetencyCriterionWeight::create([
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'FK_AREA_CONHECIMENTO' => $knowledgeArea->PK_AREA_CONHECIMENTO,
            'NU_PESO_CRIT_COMPETENCIA' => 10.5,
        ]);

        $this->assertTrue($user->journeys->first()->is($journey));
        $this->assertTrue($journey->usuario->is($user));
        $this->assertTrue($journey->journeyProfile->is($journeyProfile));
        $this->assertTrue($journey->acquiredKnowledges->first()->competency->is($competency));
        $this->assertTrue($journey->professionalCertifications->first()->is($certification));
        $this->assertTrue($competency->knowledgeArea->is($knowledgeArea));
        $this->assertTrue($competency->knowledgeDomain->is($knowledgeDomain));
        $this->assertTrue($competency->difficulty->is($difficulty));
        $this->assertTrue($journeyProfile->knowledgeDomain->is($knowledgeDomain));
        $this->assertTrue($certification->knowledgeDomain->is($knowledgeDomain));
        $this->assertTrue($journeyProfile->competencies->first()->is($competency));
        $this->assertTrue($journey->englishSubcriterion->is($subcriterion));
        $this->assertTrue($journey->higherEducationCourses->first()->is($higherEducation));
        $this->assertTrue($higherEducation->subcriterion->is($subcriterion));
        $this->assertSame(85, $journey->NU_PONTUACAO_TOTAL);
    }

    private function createLegacyJornadaSchema(): void
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
        });

        Schema::create('TB_AREA_CONHECIMENTO', function (Blueprint $table): void {
            $table->increments('PK_AREA_CONHECIMENTO');
            $table->string('NO_AREA_CONHECIMENTO_PT', 100)->nullable();
            $table->string('NO_AREA_CONHECIMENTO_EN', 100)->nullable();
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
            $table->unsignedInteger('FK_DOMINIO_CONHECIMENTO')->nullable();
            $table->string('NO_CERTIFICACAO_PROFISSIONAL', 100)->nullable();
        });

        Schema::create('TB_PROFICIENCIA_INGLES', function (Blueprint $table): void {
            $table->increments('PK_PROFICIENCIA_INGLES');
            $table->string('NO_PROFICIENCIA_INGLES_PT', 40)->nullable();
            $table->string('NO_PROFICIENCIA_INGLES_EN', 40)->nullable();
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
            $table->string('DS_EMAIL', 100)->nullable();
            $table->string('DS_SENHA', 255)->nullable();
            $table->integer('ST_ATIVO')->nullable();
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
            $table->timestamp('DH_CRIADO_EM')->nullable();
            $table->timestamp('DH_FIM')->nullable();
        });
    }

    private function seedLegacyJornadaSchema(): void
    {
        foreach ([
            1 => ['Privacidade', 'Privacy'],
            2 => ['Segurança da Informação', 'Information Security'],
            3 => ['Conjunta (Privacidade/Segurança)', 'Joint (Privacy/security)'],
        ] as $id => [$namePt, $nameEn]) {
            DB::table('TB_DOMINIO_CONHECIMENTO')->insert([
                'PK_DOMINIO_CONHECIMENTO' => $id,
                'NO_DOMINIO_CONHECIMENTO_PT' => $namePt,
                'NO_DOMINIO_CONHECIMENTO_EN' => $nameEn,
            ]);
        }

        foreach ($this->legacyJourneyProfiles() as $id => [$domainId, $namePt, $nameEn]) {
            DB::table('TB_PERFIS_JORNADA')->insert([
                'PK_PERFIS_JORNADA' => $id,
                'FK_DOMINIO_CONHECIMENTO' => $domainId,
                'NO_PERFIS_PT' => $namePt,
                'NO_PERFIS_EN' => $nameEn,
                'DS_PERFIS_PT' => "Descricao legada {$id}",
                'DS_PERFIS_EN' => "Legacy description {$id}",
            ]);
        }

        foreach ($this->legacyKnowledgeAreas() as $id => $name) {
            DB::table('TB_AREA_CONHECIMENTO')->insert([
                'PK_AREA_CONHECIMENTO' => $id,
                'NO_AREA_CONHECIMENTO_PT' => $name,
            ]);
        }

        foreach ([
            1 => ['Básico - Leitura', 'Basic - Reading'],
            2 => ['Intermediário - Leitura e Escrita', 'Intermediary - Reading and Writing'],
            3 => ['Avançado - Fluente', 'Advanced - Fluent'],
        ] as $id => [$namePt, $nameEn]) {
            DB::table('TB_PROFICIENCIA_INGLES')->insert([
                'PK_PROFICIENCIA_INGLES' => $id,
                'NO_PROFICIENCIA_INGLES_PT' => $namePt,
                'NO_PROFICIENCIA_INGLES_EN' => $nameEn,
            ]);
        }

        DB::table('TB_CURSO_SUPERIOR')->insert([
            'PK_CURSO_SUPERIOR' => 1,
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
            'TP_CURSO_SUPERIOR' => 1,
        ]);
        DB::table('TB_POS_GRADUACAO')->insert([
            'PK_POS_GRADUACAO' => 1,
            'NO_POS_GRADUACAO_PT' => 'Governança de Privacidade',
            'NO_POS_GRADUACAO_EN' => 'Privacy Governance',
            'TP_POS_GRADUACAO' => 3,
        ]);
        DB::table('TB_TIPO_PERFIL')->insert(['PK_TIPO_PERFIL' => 1, 'NO_TIPO_PERFIL' => 'Usuário']);
        DB::table('TB_USUARIO')->insert([
            'PK_USUARIO' => 1,
            'FK_TIPO_PERFIL' => 1,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria@example.com',
            'ST_ATIVO' => 1,
        ]);
        DB::table('TB_JORNADA')->insert([
            'PK_JORNADA' => 1,
            'FK_USUARIO' => 1,
            'FK_PERFIS_JORNADA' => 20,
            'FK_PROFICIENCIA_INGLES' => 2,
            'FK_CURSO_SUPERIOR' => 1,
            'FK_POS_GRADUACAO' => 1,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function legacyKnowledgeAreas(): array
    {
        return [
            1 => 'Gestão de Privacidade',
            2 => 'Estratégias de Privacidade',
            3 => 'Documentos Orientativos',
            4 => 'Gestão de Riscos e Conformidade',
            5 => 'Conhecimentos em Tecnologia da Informação',
            6 => 'Privacy By Design',
            7 => 'Gestão de Segurança da Informação',
            8 => 'Gestão de Riscos',
            9 => 'Gestão do Uso dos Recursos Operacionais e de Comunicações',
            10 => 'Tratamento da Informação',
            11 => 'Gestão de Ativos',
            12 => 'Gestão de Incidentes Cibernéticos',
            13 => 'Gestão de Continuidade',
            14 => 'Segurança de Controle de Acesso',
            15 => 'Security By Design',
            16 => 'Gestão de Mudança',
            17 => 'Gestão de Conformidade',
        ];
    }

    /**
     * @return array<int, array{int, string, string}>
     */
    private function legacyJourneyProfiles(): array
    {
        return [
            1 => [1, 'Encarregado pelo Tratamento de Dados Pessoais', 'Data Protection Officer'],
            2 => [1, 'Controlador', 'Controller'],
            3 => [1, 'Operador', 'Processor'],
            4 => [1, 'Titular', 'Data Subject'],
            5 => [1, 'Equipe de Apoio ao Encarregado', 'Data Protection Officer Support Team'],
            6 => [2, 'Gestor de Segurança da Informação', 'Information Security Manager'],
            7 => [2, 'Agente Responsável pela Gestão dos Ativos de Informação', 'Agent Responsible for Managing Information Assets'],
            8 => [2, 'Agente Responsável pela Gestão de Mudança nos Aspectos de Segurança da Informação', 'Agent Responsible for Managing Change Regarding Information Security Aspects'],
            9 => [2, 'Agente Responsável pela Gestão de Riscos de Segurança da Informação', 'Agent Responsible for Information Security Risk Management'],
            10 => [2, 'Agente Responsável pela Gestão de Continuidade de Negócios em Segurança da Informação', 'Agent Responsible for Information Security Business Continuity Management'],
            11 => [2, 'Agente Responsável pelo uso Seguro de Mídias Sociais', 'Agent Responsible for the Safe use of Social Media'],
            12 => [2, 'Agente Responsável pela Avaliação de Conformidade', 'Agent Responsible for the Conformity Assessment'],
            13 => [2, 'Equipe de Administração e de Gestão de Perfis Institucionais em Mídias Sociais', 'Institutional Social Media Profile Administration and Management Team'],
            14 => [2, 'Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos (ETIR)', 'Cyber Incident Prevention, Handling, and Response Team (ETIR)'],
            15 => [2, 'Gestor de Segurança e Credenciamento', 'Security and Accreditation Manager'],
            16 => [3, 'Gestor de Tecnologia da Informação e Comunicação', 'Information and Communication Technology Manager'],
            17 => [3, 'Equipe de Tecnologia da Informação', 'Information Technology Team'],
            18 => [3, 'Alta Administração', 'Senior Management'],
            19 => [3, 'Responsável pela Unidade de Controle Interno', 'Head of the Internal Control Unit'],
            20 => [3, 'Usuário', 'User'],
        ];
    }
}
