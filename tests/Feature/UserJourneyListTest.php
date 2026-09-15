<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\Usuario;
use App\Services\JourneyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserJourneyListTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_user_lists_only_own_paginated_journeys_ordered_by_most_recent(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');

        $this->journey($user, $profile, '2026-07-01 09:00:00', '2026-07-01 10:00:00');
        $recentJourney = $this->journey($user, $profile, '2026-07-02 09:00:00');
        $newestJourney = $this->journey($user, $profile, '2026-07-03 09:00:00');

        AcquiredKnowledge::create([
            'FK_JORNADA' => $recentJourney->PK_JORNADA,
            'TP_ADQUIRIDO' => 1,
        ]);

        $certification = ProfessionalCertification::create([
            'NO_CERTIFICACAO_PROFISSIONAL' => 'Certificacao A',
        ]);
        $recentJourney->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?page=1&per_page=2")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Jornadas retornadas com sucesso.')
            ->assertJsonPath('data.0.id', $newestJourney->PK_JORNADA)
            ->assertJsonPath('data.1.id', $recentJourney->PK_JORNADA)
            ->assertJsonPath('data.1.user_id', $user->PK_USUARIO)
            ->assertJsonPath('data.1.user_name', 'Maria Silva')
            ->assertJsonPath('data.1.journey_profile.id', $profile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.1.journey_profile.description', 'Privacidade')
            ->assertJsonPath('data.1.started_at', '2026-07-02 09:00:00')
            ->assertJsonPath('data.1.finished_at', null)
            ->assertJsonPath('data.1.status', 'ongoing')
            ->assertJsonPath('data.1.is_completed', false)
            ->assertJsonPath('data.1.summary.acquired_knowledges_count', 1)
            ->assertJsonPath('data.1.summary.professional_certifications_count', 1)
            ->assertJsonPath('data.1.detail.journey_id', $recentJourney->PK_JORNADA)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonPath('meta.pagination.last_page', 2)
            ->assertJsonPath('meta.pagination.has_next_page', true);
    }

    public function test_administrator_lists_journeys_from_any_user(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Seguranca');
        $journey = $this->journey($user, $profile, '2026-07-05 08:00:00', '2026-07-05 09:00:00');

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertOk()
            ->assertJsonPath('data.0.id', $journey->PK_JORNADA)
            ->assertJsonPath('data.0.user_id', $user->PK_USUARIO)
            ->assertJsonPath('data.0.user_name', 'Joao Souza')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('meta.pagination.has_next_page', false);
    }

    public function test_it_returns_stored_score_for_completed_journey_list_items(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey(
            user: $user,
            profile: $profile,
            startedAt: '2026-07-23 16:30:00',
            finishedAt: '2026-07-23 17:30:00',
            score: 126.329,
        );

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertOk()
            ->assertJsonPath('data.0.id', $journey->PK_JORNADA)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.score', 126.329);
    }

    public function test_common_user_cannot_list_journeys_from_another_user(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);

        $this->actingAs($user)
            ->getJson("/api/users/{$otherUser->PK_USUARIO}/journeys")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Voce nao possui permissao para visualizar as jornadas deste usuario.')
            ->assertJsonPath('code', 'USER_JOURNEYS_FORBIDDEN')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/users/1/journeys')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_requested_user_does_not_exist(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);

        $this->actingAs($admin)
            ->getJson('/api/users/999999/journeys')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao encontrado.')
            ->assertJsonPath('code', 'USER_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_invalid_pagination_parameters(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?page=0&per_page=101")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.page.0', 'A pagina atual deve ser maior ou igual a 1.')
            ->assertJsonPath('errors.per_page.0', 'A quantidade de registros por pagina nao pode ser maior que 100.');
    }

    public function test_it_filters_journeys_by_completed_status(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $oldCompletedJourney = $this->journey($user, $profile, '2026-07-01 09:00:00', '2026-07-01 10:00:00');
        $this->journey($user, $profile, '2026-07-02 09:00:00');
        $recentCompletedJourney = $this->journey($user, $profile, '2026-07-03 09:00:00', '2026-07-03 10:00:00');

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?status=completed")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.1.status', 'completed')
            ->assertJsonPath('meta.pagination.total', 2);

        $this->assertSame(
            [$recentCompletedJourney->PK_JORNADA, $oldCompletedJourney->PK_JORNADA],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_it_filters_journeys_by_ongoing_status(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $this->journey($user, $profile, '2026-07-01 09:00:00', '2026-07-01 10:00:00');
        $ongoingJourney = $this->journey($user, $profile, '2026-07-02 09:00:00');
        $this->journey($user, $profile, '2026-07-03 09:00:00', '2026-07-03 10:00:00');

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?status=ongoing")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ongoingJourney->PK_JORNADA)
            ->assertJsonPath('data.0.status', 'ongoing')
            ->assertJsonPath('data.0.finished_at', null)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_it_filters_journeys_by_journey_profile_id(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $privacyProfile = $this->journeyProfile('Privacidade');
        $securityProfile = $this->journeyProfile('Seguranca');
        $privacyJourney = $this->journey($user, $privacyProfile, '2026-07-01 09:00:00');
        $this->journey($user, $securityProfile, '2026-07-02 09:00:00');

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?journey_profile_id={$privacyProfile->PK_PERFIS_JORNADA}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $privacyJourney->PK_JORNADA)
            ->assertJsonPath('data.0.journey_profile.id', $privacyProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.0.journey_profile.description', 'Privacidade')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_it_filters_journeys_by_started_date_range(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $this->journey($user, $profile, '2026-07-01 09:00:00');
        $journeyInsideRange = $this->journey($user, $profile, '2026-07-15 09:00:00');
        $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?started_from=2026-07-10&started_to=2026-07-31")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journeyInsideRange->PK_JORNADA)
            ->assertJsonPath('data.0.started_at', '2026-07-15 09:00:00')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_it_filters_journeys_by_ended_date_range(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $this->journey($user, $profile, '2026-07-01 09:00:00', '2026-07-01 10:00:00');
        $journeyInsideRange = $this->journey($user, $profile, '2026-07-15 09:00:00', '2026-07-15 10:00:00');
        $this->journey($user, $profile, '2026-07-20 09:00:00');
        $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?ended_from=2026-07-10&ended_to=2026-07-31")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journeyInsideRange->PK_JORNADA)
            ->assertJsonPath('data.0.finished_at', '2026-07-15 10:00:00')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_it_rejects_invalid_filter_parameters(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?status=invalid&journey_profile_id=999999&started_from=2026/07/01&ended_from=2026-07-31&ended_to=2026-07-01")
            ->assertUnprocessable()
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'status',
                    'journey_profile_id',
                    'started_from',
                    'ended_to',
                ],
                'code',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.status.0', 'A situacao da jornada deve ser completed ou ongoing.')
            ->assertJsonPath('errors.journey_profile_id.0', 'O perfil de jornada informado nao existe.')
            ->assertJsonPath('errors.started_from.0', 'A data inicial minima deve estar no formato Y-m-d.')
            ->assertJsonPath('errors.ended_to.0', 'A data de finalizacao maxima deve ser maior ou igual a data de finalizacao minima.');
    }

    public function test_it_returns_empty_page_when_user_has_no_journeys(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 0)
            ->assertJsonPath('meta.pagination.last_page', 1)
            ->assertJsonPath('meta.pagination.has_next_page', false);
    }

    public function test_it_returns_standard_error_when_journey_search_fails(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Maria Silva', 2);
        $journeyService = Mockery::mock(JourneyService::class);
        $journeyService->shouldReceive('listForUser')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyService::class, $journeyService);

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel buscar as jornadas no momento.')
            ->assertJsonPath('code', 'JOURNEYS_SEARCH_FAILED')
            ->assertJsonPath('errors', []);
    }

    private function activeUser(string $name, int $userType): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function journeyProfile(string $description): JourneyProfile
    {
        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => $description,
        ]);

        return JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => $description,
            'DS_PERFIS_PT' => $description,
        ]);
    }

    private function journey(
        Usuario $user,
        JourneyProfile $profile,
        string $startedAt,
        ?string $finishedAt = null,
        ?float $score = null,
    ): Journey {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'NU_PONTUACAO_TOTAL' => $score,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);
    }
}
