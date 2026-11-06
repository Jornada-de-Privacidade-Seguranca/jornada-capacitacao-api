<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class UserJourneyAttachCertificationsTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_owner_attaches_certifications_to_their_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $certificationA = $this->certification('CIPP/E');
        $certificationB = $this->certification('CISSP');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", [
                'certification_ids' => [$certificationA->PK_CERTIFICACAO_PROFISSIONAL, $certificationB->PK_CERTIFICACAO_PROFISSIONAL],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Certificacoes vinculadas a jornada com sucesso.')
            ->assertJsonCount(2, 'data.professional_certifications');

        $this->assertDatabaseHas('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $certificationA->PK_CERTIFICACAO_PROFISSIONAL,
        ]);

        $this->assertDatabaseHas('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $certificationB->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
    }

    public function test_it_ignores_a_certification_already_attached_to_the_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $certification = $this->certification('CIPP/E');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", ['certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL]])
            ->assertOk();

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", ['certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL]])
            ->assertOk()
            ->assertJsonCount(1, 'data.professional_certifications');

        $this->assertSame(1, DB::table('RL_CERTIFICACAO_JORNADA')
            ->where('FK_JORNADA', $journey->PK_JORNADA)
            ->where('FK_CERTIFICACAO_PROFISSIONAL', $certification->PK_CERTIFICACAO_PROFISSIONAL)
            ->count());
    }

    public function test_common_user_cannot_attach_certifications_to_another_users_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($otherUser, $profile, '2026-08-01 09:00:00');
        $certification = $this->certification('CIPP/E');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", ['certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL]])
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');
    }

    public function test_it_returns_not_found_for_unknown_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $certification = $this->certification('CIPP/E');

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/999999/certifications', ['certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL]])
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_attach_when_journey_is_already_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');
        $certification = $this->certification('CIPP/E');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", ['certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'JOURNEY_ALREADY_FINISHED');
    }

    public function test_it_rejects_empty_certification_list(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", ['certification_ids' => []])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.certification_ids.0', 'Informe ao menos uma certificacao.');
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/journeys/1/certifications', ['certification_ids' => [1]])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_rejects_request_without_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $certification = $this->certification('CIPP/E');

        $this->actingAs($user)
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/certifications", [
                'certification_ids' => [$certification->PK_CERTIFICACAO_PROFISSIONAL],
            ])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }
}
