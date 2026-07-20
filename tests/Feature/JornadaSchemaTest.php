<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Difficulty;
use App\Models\EnglishProficiency;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
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
        $journeyProfile = JourneyProfile::create(['DS_PERFIS_PT' => 'Privacidade', 'TP_PERFIS' => 2]);
        $knowledgeArea = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Governanca']);
        $difficulty = Difficulty::create(['DS_DIFICULDADE_PT' => 'Basico']);
        $competency = Competency::create([
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

        $certification = ProfessionalCertification::create(['NO_CERTIFICACAO_PROFISSIONAL' => 'CISSP']);
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->assertTrue($user->journeys->first()->is($journey));
        $this->assertTrue($journey->usuario->is($user));
        $this->assertTrue($journey->journeyProfile->is($journeyProfile));
        $this->assertTrue($journey->acquiredKnowledges->first()->competency->is($competency));
        $this->assertTrue($journey->professionalCertifications->first()->is($certification));
        $this->assertTrue($competency->knowledgeArea->is($knowledgeArea));
        $this->assertTrue($competency->difficulty->is($difficulty));
        $this->assertTrue($journeyProfile->competencies->first()->is($competency));
    }
}
