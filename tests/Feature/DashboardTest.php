<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalExperience;
use App\Models\ProfileType;
use App\Models\Subcriterion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_authenticated_user_dashboard_data_with_user_journeys(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        $user = $this->createActiveUser('maria.silva@example.com', [
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'NO_ORGAO_EMPRESA' => 'Orgao Central',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
        ]);
        $otherUser = $this->createActiveUser('joao.souza@example.com');
        $journeyProfile = $this->createJourneyProfile();
        $experience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => '1 ano',
            'DS_EXPERIENCIA_ATUACAO_EN' => '1 year',
        ]);
        $criterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Formação Acadêmica',
            'NO_CRITERIO_EN' => 'Education',
        ]);
        $english = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Basico',
            'NO_SUBCRITERIO_EN' => 'Basic',
        ]);
        $graduation = $criterion->subcriteria()->create([
            'NO_SUBCRITERIO_PT' => 'Graduação',
            'NO_SUBCRITERIO_EN' => 'Graduation',
        ]);
        $postgraduate = $criterion->subcriteria()->create([
            'NO_SUBCRITERIO_PT' => 'Pós-Graduação',
            'NO_SUBCRITERIO_EN' => 'Postgraduate',
        ]);
        $higherEducation = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $graduation->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
        ]);
        $secondHigherEducation = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $postgraduate->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Segurança da Informação',
            'NO_CURSO_SUPERIOR_EN' => 'Information Security',
        ]);

        $oldestCompleted = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-01 08:00:00',
            '2026-07-01 09:00:00',
        );
        $thirdCompleted = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-02 08:00:00',
            '2026-07-02 09:00:00',
        );
        $secondCompleted = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-03 08:00:00',
            '2026-07-03 09:00:00',
        );
        $latestCompleted = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-04 08:00:00',
            '2026-07-04 09:00:00',
            [
                'FK_EXPERIENCIA_PROFISSIONAL' => $experience->PK_EXPERIENCIA_ATUACAO,
                'FK_ATUACAO_PERFIL' => $experience->PK_EXPERIENCIA_ATUACAO,
                'FK_SUBCRITERIOS_INGLES' => $english->PK_SUBCRITERIOS,
            ],
        );
        $latestCompleted->update(['TP_JORNADA_ATRIBUIDA' => Journey::ATTRIBUTION_ADMIN]);
        $secondCompleted->update(['TP_JORNADA_ATRIBUIDA' => null]);
        $latestCompleted->higherEducationCourses()->attach([
            $higherEducation->PK_CURSO_SUPERIOR,
            $secondHigherEducation->PK_CURSO_SUPERIOR,
        ]);
        $unfinished = $this->createJourney($user, $journeyProfile, '2026-07-05 08:00:00', null);
        $unfinished->update(['TP_JORNADA_ATRIBUIDA' => Journey::ATTRIBUTION_SELF]);
        $this->createJourney($otherUser, $journeyProfile, '2026-07-06 08:00:00', '2026-07-06 09:00:00');

        $this->loginAs('maria.silva@example.com');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados do painel recuperados com sucesso.')
            ->assertJsonPath('data.user.id', $user->PK_USUARIO)
            ->assertJsonPath('data.user.name', 'Maria Silva')
            ->assertJsonPath('data.user.email', 'maria.silva@example.com')
            ->assertJsonPath('data.user.role', 'common_user')
            ->assertJsonPath('data.user.organization', 'Orgao Central')
            ->assertJsonPath('data.user.department', 'Privacidade')
            ->assertJsonPath('data.user.registration_number', 'MAT-123')
            ->assertJsonPath('data.user.profile.profile_type_name', 'Aluno')
            ->assertJsonPath('data.journeys.has_unfinished_journey', true)
            ->assertJsonPath('data.journeys.unfinished_journey.id', $unfinished->PK_JORNADA)
            ->assertJsonPath('data.journeys.unfinished_journey.status', 'in_progress')
            ->assertJsonPath('data.journeys.unfinished_journey.origin', 'self_started')
            ->assertJsonPath('data.journeys.unfinished_journey.finished_at', null)
            ->assertJsonPath('data.journeys.latest_journey.id', $latestCompleted->PK_JORNADA)
            ->assertJsonPath('data.journeys.latest_journey.status', 'completed')
            ->assertJsonPath('data.journeys.latest_journey.origin', 'assigned')
            ->assertJsonPath('data.journeys.latest_journey.journey_profile.name_pt', 'Privacidade')
            ->assertJsonPath('data.journeys.latest_journey.journey_profile.description_pt', 'Privacidade')
            ->assertJsonPath(
                'data.journeys.latest_journey.journey_profile.knowledge_domain.name_pt',
                'Protecao de Dados',
            )
            ->assertJsonPath('data.journeys.latest_journey.professional_experience.description_pt', '1 ano')
            ->assertJsonPath('data.journeys.latest_journey.english_proficiency.name_pt', 'Basico')
            ->assertJsonCount(2, 'data.journeys.latest_journey.higher_education_courses')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.id', $higherEducation->PK_CURSO_SUPERIOR)
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.name_pt', 'Direito')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.name_en', 'Law')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.subcriterion.id', $graduation->PK_SUBCRITERIOS)
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.subcriterion.name_pt', 'Graduação')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.0.subcriterion.name_en', 'Graduation')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.id', $secondHigherEducation->PK_CURSO_SUPERIOR)
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.name_pt', 'Segurança da Informação')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.name_en', 'Information Security')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.subcriterion.id', $postgraduate->PK_SUBCRITERIOS)
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.subcriterion.name_pt', 'Pós-Graduação')
            ->assertJsonPath('data.journeys.latest_journey.higher_education_courses.1.subcriterion.name_en', 'Postgraduate')
            ->assertJsonCount(3, 'data.journeys.recent_journeys')
            ->assertJsonPath('data.journeys.recent_journeys.0.id', $secondCompleted->PK_JORNADA)
            ->assertJsonPath('data.journeys.recent_journeys.0.origin', null)
            ->assertJsonPath('data.journeys.recent_journeys.1.id', $thirdCompleted->PK_JORNADA)
            ->assertJsonPath('data.journeys.recent_journeys.2.id', $oldestCompleted->PK_JORNADA);
    }

    public function test_it_returns_empty_journey_structure_when_user_has_no_journeys(): void
    {
        $user = $this->createActiveUser('maria.silva@example.com');

        $this->loginAs('maria.silva@example.com');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->PK_USUARIO)
            ->assertJsonPath('data.journeys.has_unfinished_journey', false)
            ->assertJsonPath('data.journeys.unfinished_journey', null)
            ->assertJsonPath('data.journeys.latest_journey', null)
            ->assertJsonCount(0, 'data.journeys.recent_journeys');
    }

    public function test_it_returns_latest_journey_by_finished_at_instead_of_created_at(): void
    {
        $user = $this->createActiveUser('maria.silva@example.com');
        $journeyProfile = $this->createJourneyProfile();

        $createdMoreRecently = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-10 08:00:00',
            '2026-07-10 09:00:00',
        );
        $finishedMoreRecently = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-09 08:00:00',
            '2026-07-11 09:00:00',
        );

        $this->loginAs('maria.silva@example.com');

        $response = $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.journeys.latest_journey.id', $finishedMoreRecently->PK_JORNADA);

        $this->assertNotSame(
            $createdMoreRecently->PK_JORNADA,
            $response->json('data.journeys.latest_journey.id'),
        );
    }

    public function test_it_orders_recent_journeys_by_finished_at_with_id_as_tiebreaker(): void
    {
        $user = $this->createActiveUser('maria.silva@example.com');
        $journeyProfile = $this->createJourneyProfile();

        $olderFinished = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-12 08:00:00',
            '2026-07-10 09:00:00',
        );
        $latestJourney = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-08 08:00:00',
            '2026-07-13 09:00:00',
        );
        $sameFinishLowerId = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-11 08:00:00',
            '2026-07-12 09:00:00',
        );
        $sameFinishHigherId = $this->createJourney(
            $user,
            $journeyProfile,
            '2026-07-09 08:00:00',
            '2026-07-12 09:00:00',
        );

        $this->loginAs('maria.silva@example.com');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.journeys.latest_journey.id', $latestJourney->PK_JORNADA)
            ->assertJsonCount(3, 'data.journeys.recent_journeys')
            ->assertJsonPath('data.journeys.recent_journeys.0.id', $sameFinishHigherId->PK_JORNADA)
            ->assertJsonPath('data.journeys.recent_journeys.1.id', $sameFinishLowerId->PK_JORNADA)
            ->assertJsonPath('data.journeys.recent_journeys.2.id', $olderFinished->PK_JORNADA);
    }

    public function test_it_rejects_dashboard_without_authenticated_session(): void
    {
        $this->getJson('/api/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    private function createActiveUser(string $email, array $overrides = []): Usuario
    {
        return Usuario::create(array_merge([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => $email,
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ], $overrides));
    }

    private function createJourneyProfile(): JourneyProfile
    {
        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Protecao de Dados',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Data Protection',
        ]);

        return JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Privacidade',
            'NO_PERFIS_EN' => 'Privacy',
            'DS_PERFIS_PT' => 'Privacidade',
            'DS_PERFIS_EN' => 'Privacy',
        ]);
    }

    private function createJourney(
        Usuario $user,
        JourneyProfile $journeyProfile,
        string $startedAt,
        ?string $finishedAt,
        array $overrides = [],
    ): Journey {
        return Journey::create(array_merge([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ], $overrides));
    }

    private function loginAs(string $email): void
    {
        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->assertOk();
    }
}
