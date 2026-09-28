<?php

namespace Tests\Feature;

use App\Services\JourneyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class UserJourneyCreateTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_common_user_starts_a_new_journey_for_self(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Jornada iniciada com sucesso.')
            ->assertJsonPath('data.user_id', $user->PK_USUARIO)
            ->assertJsonPath('data.journey_profile.id', $profile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.finished_at', null)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.is_completed', false);

        $this->assertNotNull($response->json('data.id'));
        $this->assertNotNull($response->json('data.started_at'));

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $response->json('data.id'),
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_FIM' => null,
        ]);
    }

    public function test_administrator_starts_a_journey_for_any_user(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Seguranca');

        $response = $this->actingAs($admin)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->PK_USUARIO);

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $response->json('data.id'),
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_FIM' => null,
        ]);
    }

    public function test_common_user_cannot_start_journey_for_another_user(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Privacidade');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$otherUser->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Voce nao possui permissao para iniciar uma jornada para este usuario.')
            ->assertJsonPath('code', 'USER_JOURNEYS_FORBIDDEN')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/users/1/journeys', ['journey_profile_id' => 1])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_request_without_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');

        $this->actingAs($user)
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }

    public function test_it_returns_not_found_when_requested_user_does_not_exist(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $profile = $this->journeyProfile('Privacidade');

        $this->actingAs($admin)->withCsrfToken()
            ->postJson('/api/users/999999/journeys', [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao encontrado.')
            ->assertJsonPath('code', 'USER_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_journey_profile_does_not_exist(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => 999999,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Perfil de jornada nao encontrado.')
            ->assertJsonPath('code', 'JOURNEY_PROFILE_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Dados obrigatorios nao informados.')
            ->assertJsonPath('code', 'REQUIRED_FIELDS_MISSING')
            ->assertJsonPath('errors.journey_profile_id.0', 'O perfil de jornada e obrigatorio.');
    }

    public function test_it_rejects_invalid_data(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => 'abc',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Dados invalidos.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.journey_profile_id.0', 'O perfil de jornada deve ser um numero inteiro.');
    }

    #[DataProvider('invalidUserIdentifiers')]
    public function test_it_rejects_invalid_user_identifier(string $userIdentifier): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$userIdentifier}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Identificador do usuario invalido.')
            ->assertJsonPath('code', 'INVALID_USER_IDENTIFIER')
            ->assertJsonPath('errors.user.0', 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.');

        $this->assertDatabaseCount('TB_JORNADA', 0);
    }

    public static function invalidUserIdentifiers(): array
    {
        return [
            'texto' => ['abc'],
            'zero' => ['0'],
            'negativo' => ['-1'],
        ];
    }

    public function test_it_prevents_creating_new_journey_when_user_has_ongoing_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $ongoingProfile = $this->journeyProfile('Privacidade');
        $requestedProfile = $this->journeyProfile('Seguranca');
        $ongoingJourney = $this->journey($user, $ongoingProfile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $requestedProfile->PK_PERFIS_JORNADA,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Voce ja possui uma jornada em andamento. Finalize ou continue a jornada atual antes de iniciar uma nova.')
            ->assertJsonPath('code', 'JOURNEY_IN_PROGRESS')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseCount('TB_JORNADA', 1);
        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $ongoingJourney->PK_JORNADA,
            'FK_PERFIS_JORNADA' => $ongoingProfile->PK_PERFIS_JORNADA,
            'DH_FIM' => null,
        ]);
        $this->assertDatabaseMissing('TB_JORNADA', [
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $requestedProfile->PK_PERFIS_JORNADA,
        ]);
    }

    public function test_it_allows_creating_new_journey_when_previous_journeys_are_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ongoing');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $response->json('data.id'),
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_FIM' => null,
        ]);
    }

    public function test_it_returns_standard_error_when_journey_creation_fails(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journeyService = Mockery::mock(JourneyService::class);
        $journeyService->shouldReceive('startForUser')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyService::class, $journeyService);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel iniciar a jornada no momento.')
            ->assertJsonPath('code', 'JOURNEY_CREATION_FAILED')
            ->assertJsonPath('errors', []);
    }
}
