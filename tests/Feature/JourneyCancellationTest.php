<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class JourneyCancellationTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_owner_can_cancel_an_ongoing_self_started_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, Journey::ATTRIBUTION_SELF);

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Jornada cancelada com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.is_completed', false);

        $this->assertNotNull($response->json('data.cancelled_at'));
        $journey->refresh();
        $this->assertNotNull($journey->DH_CANCELADA_EM);
        $this->assertNull($journey->DH_FIM);
        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'DH_FIM' => null,
        ]);

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?status=cancelled")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.0.is_completed', false)
            ->assertJsonPath('data.0.is_cancelled', true);
    }

    public function test_owner_can_start_a_new_journey_after_cancelling_the_previous_one(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $cancelled = $this->journey($user, $profile, Journey::ATTRIBUTION_SELF);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$cancelled->PK_JORNADA}/cancel")
            ->assertOk();

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ongoing');

        $this->assertNotNull($cancelled->fresh()->DH_CANCELADA_EM);
        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $response->json('data.id'),
            'FK_USUARIO' => $user->PK_USUARIO,
            'TP_JORNADA_ATRIBUIDA' => Journey::ATTRIBUTION_SELF,
            'DH_FIM' => null,
            'DH_CANCELADA_EM' => null,
        ]);
    }

    public function test_owner_cannot_cancel_a_journey_assigned_by_an_administrator(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), Journey::ATTRIBUTION_ADMIN);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_owner_cannot_cancel_a_journey_with_unknown_origin(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'));

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_owner_cannot_cancel_a_completed_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey(
            $user,
            $this->journeyProfile('Privacidade'),
            Journey::ATTRIBUTION_SELF,
            '2026-11-10 10:00:00',
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');
    }

    public function test_owner_cannot_cancel_a_journey_twice(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), Journey::ATTRIBUTION_SELF);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertOk();

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');
    }

    public function test_user_cannot_cancel_another_users_journey(): void
    {
        $owner = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $journey = $this->journey($owner, $this->journeyProfile('Privacidade'), Journey::ATTRIBUTION_SELF);

        $this->actingAs($otherUser)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_cancellation_requires_authentication_and_csrf(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/journeys/1/cancel')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), Journey::ATTRIBUTION_SELF);

        $this->actingAs($user)
            ->withoutHeader('X-CSRF-TOKEN')
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }

    private function journey(
        Usuario $user,
        JourneyProfile $profile,
        ?int $attributionType = null,
        ?string $finishedAt = null,
    ): Journey {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'TP_JORNADA_ATRIBUIDA' => $attributionType,
            'DH_CRIADO_EM' => '2026-11-10 09:00:00',
            'DH_FIM' => $finishedAt,
        ]);
    }
}
