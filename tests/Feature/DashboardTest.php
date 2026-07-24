<?php

namespace Tests\Feature;

use App\Models\EnglishProficiency;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\PostgraduateCourse;
use App\Models\ProfessionalExperience;
use App\Models\ProfileType;
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
        $english = EnglishProficiency::create([
            'NO_PROFICIENCIA_INGLES_PT' => 'Basico',
            'NO_PROFICIENCIA_INGLES_EN' => 'Basic',
        ]);
        $higherEducation = HigherEducationCourse::create([
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
            'TP_CURSO_SUPERIOR' => 1,
        ]);
        $postgraduate = PostgraduateCourse::create([
            'NO_POS_GRADUACAO_PT' => 'Seguranca',
            'NO_POS_GRADUACAO_EN' => 'Security',
            'TP_POS_GRADUACAO' => 2,
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
                'FK_PROFICIENCIA_INGLES' => $english->PK_PROFICIENCIA_INGLES,
                'FK_CURSO_SUPERIOR' => $higherEducation->PK_CURSO_SUPERIOR,
                'FK_POS_GRADUACAO' => $postgraduate->PK_POS_GRADUACAO,
            ],
        );
        $unfinished = $this->createJourney($user, $journeyProfile, '2026-07-05 08:00:00', null);
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
            ->assertJsonPath('data.journeys.unfinished_journey.finished_at', null)
            ->assertJsonPath('data.journeys.latest_journey.id', $latestCompleted->PK_JORNADA)
            ->assertJsonPath('data.journeys.latest_journey.status', 'completed')
            ->assertJsonPath('data.journeys.latest_journey.journey_profile.description_pt', 'Privacidade')
            ->assertJsonPath(
                'data.journeys.latest_journey.journey_profile.knowledge_domain.name_pt',
                'Protecao de Dados',
            )
            ->assertJsonPath('data.journeys.latest_journey.professional_experience.description_pt', '1 ano')
            ->assertJsonPath('data.journeys.latest_journey.english_proficiency.name_pt', 'Basico')
            ->assertJsonCount(3, 'data.journeys.recent_journeys')
            ->assertJsonPath('data.journeys.recent_journeys.0.id', $secondCompleted->PK_JORNADA)
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
        $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->assertOk();
    }
}
