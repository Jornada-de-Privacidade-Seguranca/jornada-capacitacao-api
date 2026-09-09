<?php

namespace Tests\Feature;

use App\Exceptions\GeneralDataOptionsNotConfiguredException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Services\JourneyGeneralDataOptionsService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class JourneyGeneralDataOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_general_data_options_for_user_with_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->ongoingJourney($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Opcoes de dados gerais encontradas com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonCount(4, 'data.english_levels')
            ->assertJsonCount(6, 'data.professional_experiences')
            ->assertJsonCount(6, 'data.profile_experiences')
            ->assertJsonPath('data.english_levels.0.name_pt', 'Básico - Leitura')
            ->assertJsonPath('data.english_levels.3.name_pt', 'Nenhum')
            ->assertJsonPath('data.english_levels.3.name_en', 'None')
            ->assertJsonPath('data.professional_experiences.0.description_pt', 'Menos de 1 ano')
            ->assertJsonPath('data.profile_experiences.5.description_pt', 'Nenhum')
            ->assertJsonPath('data.required_fields.english_level', true)
            ->assertJsonPath('data.required_fields.professional_experience', true)
            ->assertJsonPath('data.required_fields.profile_experience', true)
            ->assertJsonMissingPath('data.english_levels.0.NU_PESO_SUBCRITERIO')
            ->assertJsonMissingPath('data.english_levels.0.weight')
            ->assertJsonMissingPath('data.english_levels.0.score')
            ->assertJsonMissingPath('data.professional_experiences.0.weight')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'journey_id',
                    'english_levels' => [
                        '*' => [
                            'id',
                            'name_pt',
                            'name_en',
                        ],
                    ],
                    'professional_experiences' => [
                        '*' => [
                            'id',
                            'description_pt',
                            'description_en',
                        ],
                    ],
                    'profile_experiences' => [
                        '*' => [
                            'id',
                            'description_pt',
                            'description_en',
                        ],
                    ],
                    'required_fields' => [
                        'english_level',
                        'professional_experience',
                        'profile_experience',
                    ],
                ],
            ]);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/current/general-data/options')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_user_has_no_ongoing_journey(): void
    {
        $user = $this->activeUser();
        Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-07-01 09:00:00',
            'DH_FIM' => '2026-07-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada atual nao encontrada.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_configuration_error_when_required_options_are_missing(): void
    {
        $user = $this->activeUser();
        $service = Mockery::mock(JourneyGeneralDataOptionsService::class);
        $service->shouldReceive('listForCurrentJourney')
            ->once()
            ->andThrow(new GeneralDataOptionsNotConfiguredException);
        $this->app->instance(JourneyGeneralDataOptionsService::class, $service);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Opcoes de dados gerais nao configuradas.')
            ->assertJsonPath('code', 'GENERAL_DATA_OPTIONS_NOT_CONFIGURED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_standard_error_when_options_search_fails(): void
    {
        $user = $this->activeUser();
        $service = Mockery::mock(JourneyGeneralDataOptionsService::class);
        $service->shouldReceive('listForCurrentJourney')
            ->once()
            ->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyGeneralDataOptionsService::class, $service);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel buscar as opcoes de dados gerais no momento.')
            ->assertJsonPath('code', 'GENERAL_DATA_OPTIONS_SEARCH_FAILED')
            ->assertJsonPath('errors', []);
    }

    private function activeUser(): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => fake()->unique()->safeEmail(),
            'ST_ATIVO' => 1,
        ]);
    }

    private function ongoingJourney(Usuario $user): Journey
    {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-08-01 09:00:00',
            'DH_FIM' => null,
        ]);
    }
}
