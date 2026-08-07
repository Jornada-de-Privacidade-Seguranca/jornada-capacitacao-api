<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Difficulty;
use App\Models\EnglishProficiency;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\KnowledgeArea;
use App\Models\PostgraduateCourse;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\ProfileType;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JornadaSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_jornada_tables_from_the_database_modeling(): void
    {
        foreach ([
            'TB_DOMINIO_CONHECIMENTO',
            'TB_PERFIS_JORNADA',
            'TB_AREA_CONHECIMENTO',
            'TB_DIFICULDADE',
            'TB_COMPETENCIAS',
            'RL_PERFIS_COMPETENCIAS',
            'TB_CURSO_SUPERIOR',
            'TB_POS_GRADUACAO',
            'TB_CERTIFICACAO_PROFISSIONAL',
            'TB_PROFICIENCIA_INGLES',
            'TB_EXPERIENCIA_ATUACAO',
            'TB_TIPO_PERFIL',
            'TB_USUARIO',
            'TB_JORNADA',
            'TB_CONHECIMENTOS_ADQUIRIDOS',
            'RL_CERTIFICACAO_JORNADA',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} was not created.");
        }
    }

    public function test_it_migrates_fixed_jornada_reference_data(): void
    {
        $this->assertDatabaseCount('TB_DOMINIO_CONHECIMENTO', 3);
        $this->assertDatabaseCount('TB_PERFIS_JORNADA', 20);
        $this->assertDatabaseCount('TB_AREA_CONHECIMENTO', 17);
        $this->assertDatabaseCount('TB_DIFICULDADE', 5);
        $this->assertDatabaseCount('TB_PROFICIENCIA_INGLES', 3);
        $this->assertDatabaseCount('TB_EXPERIENCIA_ATUACAO', 5);
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
        ]);
        $this->assertDatabaseHas('TB_DIFICULDADE', [
            'PK_DIFICULDADE' => 5,
            'NO_DIFICULDADE_PT' => 'Especialista',
        ]);
        $this->assertDatabaseHas('TB_PROFICIENCIA_INGLES', [
            'PK_PROFICIENCIA_INGLES' => 3,
            'NO_PROFICIENCIA_INGLES_PT' => 'Avançado - Fluente',
        ]);
        $this->assertDatabaseHas('TB_EXPERIENCIA_ATUACAO', [
            'PK_EXPERIENCIA_ATUACAO' => 5,
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Mais de 10 anos',
        ]);
        $this->assertDatabaseHas('TB_TIPO_PERFIL', [
            'PK_TIPO_PERFIL' => 3,
            'NO_TIPO_PERFIL' => 'Usuário',
        ]);
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
        $english = EnglishProficiency::create(['NO_PROFICIENCIA_INGLES_PT' => 'Basico']);
        $higherEducation = HigherEducationCourse::create(['NO_CURSO_SUPERIOR_PT' => 'Direito']);
        $postgraduate = PostgraduateCourse::create(['NO_POS_GRADUACAO_PT' => 'Seguranca']);

        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_PROFICIENCIA_INGLES' => $english->PK_PROFICIENCIA_INGLES,
            'FK_CURSO_SUPERIOR' => $higherEducation->PK_CURSO_SUPERIOR,
            'FK_POS_GRADUACAO' => $postgraduate->PK_POS_GRADUACAO,
        ]);

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
    }
}
