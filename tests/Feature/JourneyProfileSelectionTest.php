<?php

namespace Tests\Feature;

use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyProfileSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_authenticated_users_selected_journey_profile(): void
    {
        $profile = $this->journeyProfile('Privacidade');
        $user = $this->activeUser('Maria Silva');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Perfil de jornada selecionado com sucesso.')
            ->assertJsonPath('data.journey_profile_id', $profile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.journey_profile.id', $profile->PK_PERFIS_JORNADA)
            ->assertJsonMissingPath('data.journey_profile.competencies_count')
            ->assertJsonMissingPath('data.journey_profile.knowledge_areas_count');

        $this->actingAs($user)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.profile.journey_profile_id', $profile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.profile.journey_profile_name', $profile->NO_PERFIS_PT)
            ->assertJsonPath('data.profile.journey_profile_name_en', $profile->NO_PERFIS_EN);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIL_JORNADA' => $profile->PK_PERFIS_JORNADA,
        ]);
    }

    public function test_it_allows_updating_the_selection_until_the_first_journey_is_started(): void
    {
        $firstProfile = $this->journeyProfile('Privacidade');
        $secondProfile = $this->journeyProfile('Seguranca');
        $user = $this->activeUser('Maria Silva');
        $this->selectProfile($user, $firstProfile);

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [
                'journey_profile_id' => $secondProfile->PK_PERFIS_JORNADA,
            ])
            ->assertOk()
            ->assertJsonPath('data.journey_profile_id', $secondProfile->PK_PERFIS_JORNADA);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIL_JORNADA' => $secondProfile->PK_PERFIS_JORNADA,
        ]);
    }

    public function test_it_rejects_selection_after_the_user_has_started_a_journey(): void
    {
        $firstProfile = $this->journeyProfile('Privacidade');
        $secondProfile = $this->journeyProfile('Seguranca');
        $user = $this->activeUser('Maria Silva');
        $this->selectProfile($user, $firstProfile);
        $user->journeys()->create([
            'FK_PERFIS_JORNADA' => $firstProfile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => '2026-08-01 09:00:00',
            'DH_FIM' => null,
        ]);

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [
                'journey_profile_id' => $secondProfile->PK_PERFIS_JORNADA,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'JOURNEY_PROFILE_SELECTION_LOCKED');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIL_JORNADA' => $firstProfile->PK_PERFIS_JORNADA,
        ]);
    }

    public function test_it_rejects_unknown_journey_profiles(): void
    {
        $user = $this->activeUser('Maria Silva');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [
                'journey_profile_id' => 999999,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'JOURNEY_PROFILE_NOT_FOUND');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIL_JORNADA' => null,
        ]);
    }

    public function test_it_rejects_missing_selection_and_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.journey_profile_id.0', 'Selecione um perfil de jornada.');

        $this->actingAs($user)
            ->withoutHeader('X-CSRF-TOKEN')
            ->putJson('/api/me/journey-profile', ['journey_profile_id' => 1])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }

    private function selectProfile(Usuario $user, JourneyProfile $profile): void
    {
        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/me/journey-profile', [
                'journey_profile_id' => $profile->PK_PERFIS_JORNADA,
            ])
            ->assertOk();
    }

    private function activeUser(string $name): Usuario
    {
        return Usuario::create([
            'FK_TIPO_PERFIL' => 3,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function journeyProfile(string $name): JourneyProfile
    {
        $domain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => $name]);

        return JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => $name,
            'NO_PERFIS_EN' => $name.' Profile',
            'DS_PERFIS_PT' => $name,
        ]);
    }
}
