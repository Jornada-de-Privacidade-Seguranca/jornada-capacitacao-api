<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class UserJourneyUpdateAnswersTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_owner_updates_the_general_answers_of_their_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $experience = $this->professionalExperience('Junior');
        $englishSubcriterion = $this->subcriterion('Avancado - Fluente');

        $this->actingAs($user)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'professional_experience_id' => $experience->PK_EXPERIENCIA_ATUACAO,
                'english_proficiency_id' => $englishSubcriterion->PK_SUBCRITERIOS,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Respostas da jornada atualizadas com sucesso.')
            ->assertJsonPath('data.answers.professional_experience.id', $experience->PK_EXPERIENCIA_ATUACAO)
            ->assertJsonPath('data.answers.english_proficiency.id', $englishSubcriterion->PK_SUBCRITERIOS);

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_SUBCRITERIOS_INGLES' => $englishSubcriterion->PK_SUBCRITERIOS,
        ]);
    }

    public function test_administrator_updates_answers_for_any_user_journey(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $user = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Seguranca');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $experience = $this->professionalExperience('Pleno');

        $this->actingAs($admin)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'profile_experience_id' => $experience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_ATUACAO_PERFIL' => $experience->PK_EXPERIENCIA_ATUACAO,
        ]);
    }

    public function test_common_user_cannot_update_another_users_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($otherUser, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'professional_experience_id' => $this->professionalExperience()->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'JOURNEY_FORBIDDEN');
    }

    public function test_it_returns_not_found_for_unknown_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/journeys/999999/answers', [
                'professional_experience_id' => $this->professionalExperience()->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_update_when_journey_is_already_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'professional_experience_id' => $this->professionalExperience()->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'JOURNEY_ALREADY_FINISHED');
    }

    public function test_it_rejects_a_subcriterion_that_is_not_an_english_proficiency_level(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $graduationSubcriterion = $this->subcriterion('Graduacao', 'Formacao Academica');

        $this->actingAs($user)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'english_proficiency_id' => $graduationSubcriterion->PK_SUBCRITERIOS,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.english_proficiency_id.0', 'A proficiencia em ingles informada nao existe.');
    }

    public function test_it_rejects_invalid_data(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [
                'professional_experience_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.professional_experience_id.0', 'A experiencia profissional informada nao existe.');
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->withCsrfToken()
            ->patchJson('/api/journeys/1/answers', [])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_rejects_request_without_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)
            ->patchJson("/api/journeys/{$journey->PK_JORNADA}/answers", [])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }
}
