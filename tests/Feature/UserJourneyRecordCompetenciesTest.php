<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class UserJourneyRecordCompetenciesTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_owner_records_acquired_competencies_for_their_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $acquired = $this->competency('LGPD Fundamentos', $profile);
        $notAcquired = $this->competency('ISO 27001 Avancado', $profile);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [
                    ['competency_id' => $acquired->PK_COMPETENCIAS, 'is_acquired' => true],
                    ['competency_id' => $notAcquired->PK_COMPETENCIAS, 'is_acquired' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Competencias da jornada atualizadas com sucesso.')
            ->assertJsonCount(2, 'data.acquired_knowledges');

        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $acquired->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
        ]);

        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $notAcquired->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 0,
        ]);
    }

    public function test_it_updates_the_acquisition_status_when_the_same_competency_is_sent_again(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $competency = $this->competency('LGPD Fundamentos', $profile);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => false]],
            ])
            ->assertOk();

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.acquired_knowledges');

        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
        ]);
    }

    public function test_common_user_cannot_record_competencies_for_another_users_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($otherUser, $profile, '2026-08-01 09:00:00');
        $competency = $this->competency('LGPD Fundamentos');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'JOURNEY_FORBIDDEN');
    }

    public function test_it_returns_not_found_for_unknown_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $competency = $this->competency('LGPD Fundamentos');

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/999999/competencies', [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_record_when_journey_is_already_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');
        $competency = $this->competency('LGPD Fundamentos');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'JOURNEY_ALREADY_FINISHED');
    }

    public function test_it_rejects_a_competency_that_does_not_belong_to_the_journey_profile(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $otherProfile = $this->journeyProfile('Seguranca da Informacao');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $competency = $this->competency('ISO 27001 Avancado', $otherProfile);

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COMPETENCY_NOT_IN_JOURNEY_PROFILE');

        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
        ]);
    }

    public function test_it_rejects_empty_competency_list(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", ['competencies' => []])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.competencies.0', 'Informe ao menos uma competencia.');
    }

    public function test_it_rejects_missing_is_acquired_flag(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $competency = $this->competency('LGPD Fundamentos');

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertSame(
            'E obrigatorio informar se a competencia foi adquirida.',
            $response->json('errors')['competencies.0.is_acquired'][0],
        );
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/journeys/1/competencies', ['competencies' => [['competency_id' => 1, 'is_acquired' => true]]])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_rejects_request_without_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $competency = $this->competency('LGPD Fundamentos', $profile);

        $this->actingAs($user)
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/competencies", [
                'competencies' => [['competency_id' => $competency->PK_COMPETENCIAS, 'is_acquired' => true]],
            ])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }
}
