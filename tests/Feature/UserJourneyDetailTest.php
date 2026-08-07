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
use App\Models\KnowledgeDomain;
use App\Models\PostgraduateCourse;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\Usuario;
use App\Services\JourneyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserJourneyDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_user_can_view_own_journey_detail(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completeJourneyFor($user, '2026-07-23 16:30:00');

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Jornada encontrada com sucesso.')
            ->assertJsonPath('data.id', $journey->PK_JORNADA)
            ->assertJsonPath('data.user.id', $user->PK_USUARIO)
            ->assertJsonPath('data.user.name', 'Maria Silva')
            ->assertJsonPath('data.user.email', 'maria.silva@example.com')
            ->assertJsonPath('data.journey_profile.name_pt', 'Encarregado')
            ->assertJsonPath('data.journey_profile.knowledge_domain.name_pt', 'Privacidade')
            ->assertJsonPath('data.journey_profile.competencies.0.name_pt', 'LGPD')
            ->assertJsonPath('data.started_at', '2026-07-23 16:30:00')
            ->assertJsonPath('data.finished_at', null)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.answers.professional_experience.description_pt', '1 a 3 anos')
            ->assertJsonPath('data.answers.profile_experience.description_pt', 'Mais de 5 anos')
            ->assertJsonPath('data.answers.english_proficiency.name_pt', 'Intermediario')
            ->assertJsonPath('data.answers.higher_education_course.name_pt', 'Direito')
            ->assertJsonPath('data.answers.postgraduate_course.name_pt', 'Especializacao')
            ->assertJsonPath('data.acquired_knowledges.0.type_acquired', 1)
            ->assertJsonPath('data.acquired_knowledges.0.is_acquired', true)
            ->assertJsonPath('data.acquired_knowledges.0.competency.name_pt', 'LGPD')
            ->assertJsonPath('data.professional_certifications.0.name', 'CIPM')
            ->assertJsonPath('data.professional_certifications.0.knowledge_domain.name_pt', 'Privacidade')
            ->assertJsonPath('data.summary.acquired_knowledges_count', 1)
            ->assertJsonPath('data.summary.professional_certifications_count', 1);
    }

    public function test_administrator_can_view_journey_from_any_user(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $journey = $this->completeJourneyFor($user, '2026-07-23 16:30:00', '2026-07-23 17:30:00');

        $this->actingAs($admin)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.id', $journey->PK_JORNADA)
            ->assertJsonPath('data.user.id', $user->PK_USUARIO)
            ->assertJsonPath('data.finished_at', '2026-07-23 17:30:00')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.answers.professional_experience.description_pt', '1 a 3 anos')
            ->assertJsonPath('data.answers.profile_experience.description_pt', 'Mais de 5 anos')
            ->assertJsonPath('data.answers.english_proficiency.name_pt', 'Intermediario')
            ->assertJsonPath('data.answers.higher_education_course.name_pt', 'Direito')
            ->assertJsonPath('data.answers.postgraduate_course.name_pt', 'Especializacao');
    }

    public function test_it_rejects_completed_journey_with_missing_required_data(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-07-23 16:30:00',
            'DH_FIM' => '2026-07-23 17:30:00',
        ]);

        $this->actingAs($admin)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada concluida possui dados obrigatorios incompletos.')
            ->assertJsonPath('code', 'INCOMPLETE_JOURNEY')
            ->assertJsonPath('errors.fields.0', 'journey_profile')
            ->assertJsonPath('errors.fields.1', 'answers.professional_experience')
            ->assertJsonPath('errors.fields.2', 'answers.profile_experience')
            ->assertJsonPath('errors.fields.3', 'answers.english_proficiency')
            ->assertJsonPath('errors.fields.4', 'answers.higher_education_course')
            ->assertJsonPath('errors.fields.5', 'answers.postgraduate_course');
    }

    public function test_common_user_cannot_view_journey_from_another_user(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $journey = $this->completeJourneyFor($otherUser, '2026-07-23 16:30:00');

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Voce nao possui permissao para visualizar esta jornada.')
            ->assertJsonPath('code', 'JOURNEY_FORBIDDEN')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/1')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_journey_does_not_exist(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);

        $this->actingAs($admin)
            ->getJson('/api/journeys/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada nao encontrada.')
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_invalid_journey_identifier(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);

        $this->actingAs($admin)
            ->getJson('/api/journeys/invalid')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Identificador da jornada invalido.')
            ->assertJsonPath('code', 'INVALID_JOURNEY_IDENTIFIER')
            ->assertJsonPath('errors.journey.0', 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.');
    }

    public function test_it_returns_standard_error_when_journey_detail_search_fails(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $journeyService = Mockery::mock(JourneyService::class);
        $journeyService->shouldReceive('findForAuthenticatedUser')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyService::class, $journeyService);

        $this->actingAs($admin)
            ->getJson('/api/journeys/1')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel buscar os dados da jornada no momento.')
            ->assertJsonPath('code', 'JOURNEY_SEARCH_FAILED')
            ->assertJsonPath('errors', []);
    }

    private function activeUser(string $name, int $userType): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'NO_ORGAO_EMPRESA' => 'Jornada',
            'NO_SETOR' => 'Capacitacao',
            'NU_MATRICULA' => '123',
            'ST_ATIVO' => 1,
        ]);
    }

    private function completeJourneyFor(Usuario $user, string $startedAt, ?string $finishedAt = null): Journey
    {
        $domain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $area = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Governanca',
            'NO_AREA_CONHECIMENTO_EN' => 'Governance',
        ]);
        $difficulty = Difficulty::create([
            'NO_DIFICULDADE_PT' => 'Basico',
            'NO_DIFICULDADE_EN' => 'Basic',
        ]);
        $competency = Competency::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_ORIGEM_COMPETENCIAS_PT' => 'Referencial',
            'NO_ORIGEM_COMPETENCIAS_EN' => 'Reference',
            'NO_COMPETENCIAS_PT' => 'LGPD',
            'NO_COMPETENCIAS_EN' => 'LGPD',
            'DS_COMPETENCIAS_PT' => 'Fundamentos da LGPD',
            'DS_COMPETENCIAS_EN' => 'LGPD fundamentals',
        ]);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado',
            'NO_PERFIS_EN' => 'DPO',
            'DS_PERFIS_PT' => 'Privacidade e Seguranca',
            'DS_PERFIS_EN' => 'Privacy and Security',
        ]);
        $profile->competencies()->attach($competency->PK_COMPETENCIAS);

        $professionalExperience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => '1 a 3 anos',
            'DS_EXPERIENCIA_ATUACAO_EN' => '1 to 3 years',
        ]);
        $profileExperience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Mais de 5 anos',
            'DS_EXPERIENCIA_ATUACAO_EN' => 'More than 5 years',
        ]);
        $englishProficiency = EnglishProficiency::create([
            'NO_PROFICIENCIA_INGLES_PT' => 'Intermediario',
            'NO_PROFICIENCIA_INGLES_EN' => 'Intermediate',
        ]);
        $higherEducationCourse = HigherEducationCourse::create([
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
            'TP_CURSO_SUPERIOR' => 1,
        ]);
        $postgraduateCourse = PostgraduateCourse::create([
            'NO_POS_GRADUACAO_PT' => 'Especializacao',
            'NO_POS_GRADUACAO_EN' => 'Specialization',
            'TP_POS_GRADUACAO' => 1,
        ]);

        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_PROFICIENCIA_INGLES' => $englishProficiency->PK_PROFICIENCIA_INGLES,
            'FK_CURSO_SUPERIOR' => $higherEducationCourse->PK_CURSO_SUPERIOR,
            'FK_POS_GRADUACAO' => $postgraduateCourse->PK_POS_GRADUACAO,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);

        AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
        ]);

        $certification = ProfessionalCertification::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_CERTIFICACAO_PROFISSIONAL' => 'CIPM',
        ]);
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        return $journey;
    }
}
