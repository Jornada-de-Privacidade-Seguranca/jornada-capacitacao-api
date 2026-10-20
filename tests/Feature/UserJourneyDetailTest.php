<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Criterion;
use App\Models\Difficulty;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
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
            ->assertJsonPath('data.score', null)
            ->assertJsonPath('data.answers.professional_experience.description_pt', '1 a 3 anos')
            ->assertJsonPath('data.answers.profile_experience.description_pt', 'Mais de 5 anos')
            ->assertJsonPath('data.answers.english_proficiency.name_pt', 'Intermediario')
            ->assertJsonCount(2, 'data.answers.higher_education_courses')
            ->assertJsonPath('data.answers.higher_education_courses.0.name_pt', 'Direito')
            ->assertJsonPath('data.answers.higher_education_courses.0.subcriterion.name_pt', 'Graduacao')
            ->assertJsonPath('data.answers.higher_education_courses.1.name_pt', 'Especializacao')
            ->assertJsonPath('data.answers.higher_education_courses.1.subcriterion.name_pt', 'Pos-Graduacao')
            ->assertJsonPath('data.acquired_knowledges.0.type_acquired', 1)
            ->assertJsonPath('data.acquired_knowledges.0.is_acquired', true)
            ->assertJsonPath('data.acquired_knowledges.0.competency.name_pt', 'LGPD')
            ->assertJsonPath('data.professional_certifications.0.name', 'CIPM')
            ->assertJsonPath('data.professional_certifications.0.knowledge_domain.name_pt', 'Privacidade')
            ->assertJsonPath('data.summary.acquired_knowledges_count', 1)
            ->assertJsonPath('data.summary.professional_certifications_count', 1);
    }

    public function test_it_groups_acquired_and_not_acquired_knowledges_by_domain(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completeJourneyFor($user, '2026-07-23 16:30:00');
        $profile = JourneyProfile::query()->findOrFail($journey->FK_PERFIS_JORNADA);
        $privacyDomain = KnowledgeDomain::query()->findOrFail($profile->FK_DOMINIO_CONHECIMENTO);
        $governanceDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Governanca',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Governance',
        ]);
        $difficulty = Difficulty::query()->firstOrFail();
        $area = KnowledgeArea::query()->firstOrFail();

        $notAcquiredCompetency = $this->competencyForDomain($privacyDomain, $area, $difficulty, 'Resposta negativa');
        $unansweredCompetency = $this->competencyForDomain($governanceDomain, $area, $difficulty, 'Sem resposta');
        $profile->competencies()->attach([$notAcquiredCompetency->PK_COMPETENCIAS, $unansweredCompetency->PK_COMPETENCIAS]);
        AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $notAcquiredCompetency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 0,
        ]);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonCount(2, 'data.knowledges_by_domain')
            ->assertJsonPath('data.knowledges_by_domain.0.knowledge_domain.name_pt', 'Privacidade')
            ->assertJsonCount(1, 'data.knowledges_by_domain.0.acquired_knowledges')
            ->assertJsonPath('data.knowledges_by_domain.0.acquired_knowledges.0.competency.name_pt', 'LGPD')
            ->assertJsonPath('data.knowledges_by_domain.0.acquired_knowledges.0.competency.knowledge_area.name_pt', 'Governanca')
            ->assertJsonPath('data.knowledges_by_domain.0.acquired_knowledges.0.competency.difficulty.name_pt', 'Basico')
            ->assertJsonCount(1, 'data.knowledges_by_domain.0.not_acquired_knowledges')
            ->assertJsonPath('data.knowledges_by_domain.0.not_acquired_knowledges.0.is_acquired', false)
            ->assertJsonPath('data.knowledges_by_domain.0.not_acquired_knowledges.0.competency.name_pt', 'Resposta negativa')
            ->assertJsonPath('data.knowledges_by_domain.1.knowledge_domain.name_pt', 'Governanca')
            ->assertJsonCount(0, 'data.knowledges_by_domain.1.acquired_knowledges')
            ->assertJsonPath('data.knowledges_by_domain.1.not_acquired_knowledges.0.type_acquired', null)
            ->assertJsonPath('data.knowledges_by_domain.1.not_acquired_knowledges.0.competency.name_pt', 'Sem resposta')
            ->assertJsonMissingPath('data.knowledges_by_domain.0.acquired_knowledges.0.competency.weight')
            ->assertJsonMissingPath('data.knowledges_by_domain.0.acquired_knowledges.0.competency.weights');
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
            ->assertJsonCount(2, 'data.answers.higher_education_courses')
            ->assertJsonPath('data.answers.higher_education_courses.0.name_pt', 'Direito')
            ->assertJsonPath('data.answers.higher_education_courses.1.name_pt', 'Especializacao');
    }

    public function test_it_returns_stored_score_for_completed_journey_detail(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completeJourneyFor(
            user: $user,
            startedAt: '2026-07-23 16:30:00',
            finishedAt: '2026-07-23 17:30:00',
            score: 126.329,
        );

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.score', 126.329);

        $this->assertSame(126.329, $journey->fresh()->NU_PONTUACAO_TOTAL);
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
            ->assertJsonPath('errors.fields.3', 'answers.english_proficiency');
    }

    public function test_it_rejects_completed_journey_when_journey_profile_knowledge_domain_is_missing(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $journey = $this->completeJourneyFor($user, '2026-07-23 16:30:00', '2026-07-23 17:30:00');
        JourneyProfile::query()
            ->whereKey($journey->FK_PERFIS_JORNADA)
            ->update(['FK_DOMINIO_CONHECIMENTO' => null]);

        $this->actingAs($admin)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada concluida possui dados obrigatorios incompletos.')
            ->assertJsonPath('code', 'INCOMPLETE_JOURNEY')
            ->assertJsonPath('errors.fields', ['journey_profile.knowledge_domain']);
    }

    public function test_it_rejects_completed_journey_when_acquired_knowledge_competency_is_missing(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $journey = $this->completeJourneyFor($user, '2026-07-23 16:30:00', '2026-07-23 17:30:00');
        AcquiredKnowledge::query()
            ->where('FK_JORNADA', $journey->PK_JORNADA)
            ->update(['FK_COMPETENCIAS' => null]);

        $this->actingAs($admin)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada concluida possui dados obrigatorios incompletos.')
            ->assertJsonPath('code', 'INCOMPLETE_JOURNEY')
            ->assertJsonPath('errors.fields', ['acquired_knowledges.competency']);
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

    private function competencyForDomain(
        KnowledgeDomain $domain,
        KnowledgeArea $area,
        Difficulty $difficulty,
        string $name,
    ): Competency {
        return Competency::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_COMPETENCIAS_PT' => $name,
            'NO_COMPETENCIAS_EN' => $name,
        ]);
    }

    private function completeJourneyFor(
        Usuario $user,
        string $startedAt,
        ?string $finishedAt = null,
        ?float $score = null,
    ): Journey {
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
        $criterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Formacao Academica',
            'NO_CRITERIO_EN' => 'Education',
        ]);
        $englishProficiency = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Intermediario',
            'NO_SUBCRITERIO_EN' => 'Intermediate',
        ]);
        $graduation = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Graduacao',
            'NO_SUBCRITERIO_EN' => 'Graduation',
        ]);
        $postgraduate = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Pos-Graduacao',
            'NO_SUBCRITERIO_EN' => 'Postgraduate',
        ]);
        $higherEducationCourse = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $graduation->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
        ]);
        $postgraduateCourse = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $postgraduate->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Especializacao',
            'NO_CURSO_SUPERIOR_EN' => 'Specialization',
        ]);

        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_SUBCRITERIOS_INGLES' => $englishProficiency->PK_SUBCRITERIOS,
            'NU_PONTUACAO_TOTAL' => $score,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);
        $journey->higherEducationCourses()->attach([
            $higherEducationCourse->PK_CURSO_SUPERIOR,
            $postgraduateCourse->PK_CURSO_SUPERIOR,
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
