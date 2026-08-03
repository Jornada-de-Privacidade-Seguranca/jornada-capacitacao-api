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
            ->assertJsonPath('message', 'Parametros de paginacao invalidos.')
            ->assertJsonPath('code', 'INVALID_PAGINATION_PARAMETERS')
            ->assertJsonPath('errors.page.0', 'A pagina atual deve ser maior ou igual a 1.')
            ->assertJsonPath('errors.per_page.0', 'A quantidade de registros por pagina nao pode ser maior que 100.');
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
            'DS_PERFIS_PT' => $description,
        ]);
    }

    private function journey(Usuario $user, JourneyProfile $profile, string $startedAt, ?string $finishedAt = null): Journey
    {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);
    }
}
