<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_assigning_journey_persists_assigned_by_and_exposes_it_across_endpoints_even_if_assigner_is_deactivated(): void
    {
        $admin = $this->activeUser('Admin Responsavel', 1);
        $profile = $this->journeyProfile('Privacidade');
        $user = $this->activeUser('Maria Silva', 2);
        $user->update(['FK_PERFIL_JORNADA' => $profile->PK_PERFIS_JORNADA]);

        $createResponse = $this->actingAs($admin)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('data.origin', 'assigned')
            ->assertJsonPath('data.assigned_by.id', $admin->PK_USUARIO)
            ->assertJsonPath('data.assigned_by.name', 'Admin Responsavel');

        $journeyId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journeyId,
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_USUARIO_ATRIBUIU' => $admin->PK_USUARIO,
            'TP_JORNADA_ATRIBUIDA' => Journey::ATTRIBUTION_ADMIN,
        ]);

        // Desativa o administrador que atribuiu posteriormente
        $admin->update(['ST_ATIVO' => 2]);

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertOk()
            ->assertJsonPath('data.0.origin', 'assigned')
            ->assertJsonPath('data.0.assigned_by.id', $admin->PK_USUARIO)
            ->assertJsonPath('data.0.assigned_by.name', 'Admin Responsavel');

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journeyId}")
            ->assertOk()
            ->assertJsonPath('data.origin', 'assigned')
            ->assertJsonPath('data.assigned_by.id', $admin->PK_USUARIO)
            ->assertJsonPath('data.assigned_by.name', 'Admin Responsavel');

        $this->actingAs($user)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.journeys.unfinished_journey.origin', 'assigned')
            ->assertJsonPath('data.journeys.unfinished_journey.assigned_by.id', $admin->PK_USUARIO)
            ->assertJsonPath('data.journeys.unfinished_journey.assigned_by.name', 'Admin Responsavel');
    }

    public function test_self_started_and_legacy_journeys_return_null_assigned_by(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $selfJourney = $this->journey($user, $profile, Journey::ATTRIBUTION_SELF);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$selfJourney->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.origin', 'self_started')
            ->assertJsonPath('data.assigned_by', null);

        $this->actingAs($user)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.journeys.unfinished_journey.origin', 'self_started')
            ->assertJsonPath('data.journeys.unfinished_journey.assigned_by', null);
    }

    public function test_assigner_with_permission_can_cancel_assigned_journey_and_unlocks_new_journeys(): void
    {
        $manager = Usuario::create([
            'FK_TIPO_PERFIL' => 2,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Gestor Atribuidor',
            'DS_EMAIL' => 'gestor.atribuidor@example.com',
            'ST_ATIVO' => 1,
        ]);
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $user->update(['FK_PERFIL_JORNADA' => $profile->PK_PERFIS_JORNADA]);

        $assignedJourney = $this->journey(
            $user,
            $profile,
            Journey::ATTRIBUTION_ADMIN,
            null,
            $manager->PK_USUARIO,
        );

        $this->actingAs($manager)->withCsrfToken()
            ->postJson("/api/journeys/{$assignedJourney->PK_JORNADA}/cancel")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.journey_id', $assignedJourney->PK_JORNADA)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.is_completed', false);

        $this->assertNotNull($assignedJourney->fresh()->DH_CANCELADA_EM);

        // Titular fica liberado para iniciar nova jornada propria ou receber nova atribuicao
        $this->actingAs($manager)->withCsrfToken()
            ->postJson("/api/users/{$user->PK_USUARIO}/journeys", [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertCreated()
            ->assertJsonPath('data.origin', 'assigned')
            ->assertJsonPath('data.assigned_by.id', $manager->PK_USUARIO);
    }

    public function test_owner_cannot_cancel_journey_assigned_with_assigner_recorded(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey(
            $user,
            $this->journeyProfile('Privacidade'),
            Journey::ATTRIBUTION_ADMIN,
            null,
            $admin->PK_USUARIO,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_another_admin_or_manager_who_did_not_assign_cannot_cancel_assigned_journey(): void
    {
        $assigner = $this->activeUser('Admin Um', 1);
        $otherAdmin = $this->activeUser('Admin Dois', 1);
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey(
            $user,
            $this->journeyProfile('Privacidade'),
            Journey::ATTRIBUTION_ADMIN,
            null,
            $assigner->PK_USUARIO,
        );

        $this->actingAs($otherAdmin)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_assigner_without_assign_permission_cannot_cancel_assigned_journey(): void
    {
        $assigner = $this->activeUser('Ex Gestor', 1);
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey(
            $user,
            $this->journeyProfile('Privacidade'),
            Journey::ATTRIBUTION_ADMIN,
            null,
            $assigner->PK_USUARIO,
        );

        // Revoga permissao journeys.assign movendo para perfil comum (3)
        $assigner->update(['FK_TIPO_PERFIL' => 3]);

        $this->actingAs($assigner)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/cancel")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->assertNull($journey->fresh()->DH_CANCELADA_EM);
    }

    public function test_legacy_assigned_journey_without_assigner_cannot_be_cancelled_by_anyone(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Maria Silva', 2);
        $legacyAssigned = $this->journey(
            $user,
            $this->journeyProfile('Privacidade'),
            Journey::ATTRIBUTION_ADMIN,
            null,
            null,
        );

        $this->actingAs($admin)->withCsrfToken()
            ->postJson("/api/journeys/{$legacyAssigned->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$legacyAssigned->PK_JORNADA}/cancel")
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_CANCELLATION_NOT_ALLOWED');

        $this->assertNull($legacyAssigned->fresh()->DH_CANCELADA_EM);
    }

    public function test_assigned_by_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_11_12_000000_add_assigned_by_to_journeys.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));

        // Idempotent down
        $migration->down();
        $this->assertFalse(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));

        // Idempotent up
        $migration->up();
        $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU'));
    }

    private function journey(
        Usuario $user,
        JourneyProfile $profile,
        ?int $attributionType = null,
        ?string $finishedAt = null,
        ?int $assignedByUserId = null,
    ): Journey {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_USUARIO_ATRIBUIU' => $assignedByUserId,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'TP_JORNADA_ATRIBUIDA' => $attributionType,
            'DH_CRIADO_EM' => '2026-11-10 09:00:00',
            'DH_FIM' => $finishedAt,
        ]);
    }
}
