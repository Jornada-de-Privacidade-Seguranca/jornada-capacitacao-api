<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Journey;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Models\Usuario;
use App\Services\JourneyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CurrentJourneyGeneralDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_general_data_for_current_journey(): void
    {
        $user = $this->activeUser('Maria Silva');
        $journey = $this->journeyFor($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados gerais da jornada recuperados com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.english_level', null)
            ->assertJsonPath('data.professional_experience', null)
            ->assertJsonPath('data.profile_experience', null)
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonPath('data.status', 'ongoing');
    }

    public function test_it_returns_partially_filled_general_data_for_current_journey(): void
    {
        $user = $this->activeUser('Maria Silva');
        $englishLevel = $this->englishLevel('Intermediario', 'Intermediate', 26.05);
        $journey = $this->journeyFor($user, [
            'FK_SUBCRITERIOS_INGLES' => $englishLevel->PK_SUBCRITERIOS,
        ]);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data')
            ->assertOk()
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.english_level.id', $englishLevel->PK_SUBCRITERIOS)
            ->assertJsonPath('data.english_level.name_pt', 'Intermediario')
            ->assertJsonPath('data.english_level.name_en', 'Intermediate')
            ->assertJsonPath('data.professional_experience', null)
            ->assertJsonPath('data.profile_experience', null)
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonPath('data.status', 'ongoing');
    }

    public function test_it_returns_complete_general_data_without_weights_or_scores(): void
    {
        $user = $this->activeUser('Maria Silva');
        $englishLevel = $this->englishLevel('Avancado', 'Advanced', 63.335);
        $professionalExperience = $this->experience('1 a 3 anos', '1 to 3 years');
        $profileExperience = $this->experience('Mais de 5 anos', 'More than 5 years');
        $journey = $this->journeyFor($user, [
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_SUBCRITERIOS_INGLES' => $englishLevel->PK_SUBCRITERIOS,
            'NU_PONTUACAO_TOTAL' => 95,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data')
            ->assertOk()
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.english_level.id', $englishLevel->PK_SUBCRITERIOS)
            ->assertJsonPath('data.professional_experience.id', $professionalExperience->PK_EXPERIENCIA_ATUACAO)
            ->assertJsonPath('data.professional_experience.description_pt', '1 a 3 anos')
            ->assertJsonPath('data.profile_experience.id', $profileExperience->PK_EXPERIENCIA_ATUACAO)
            ->assertJsonPath('data.profile_experience.description_pt', 'Mais de 5 anos')
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.status', 'ongoing');

        $data = $response->json('data');

        $this->assertArrayNotHasKey('NU_PESO_SUBCRITERIO', $data['english_level']);
        $this->assertArrayNotHasKey('weight', $data['english_level']);
        $this->assertArrayNotHasKey('score', $data);
        $this->assertArrayNotHasKey('total_score', $data);
        $this->assertArrayNotHasKey('NU_PONTUACAO_TOTAL', $data);
    }

    public function test_it_returns_not_found_when_user_has_no_current_journey(): void
    {
        $user = $this->activeUser('Maria Silva');
        $otherUser = $this->activeUser('Joao Souza');
        $this->journeyFor($user, ['DH_FIM' => '2026-07-23 17:30:00']);
        $this->journeyFor($otherUser);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada em andamento nao encontrada.')
            ->assertJsonPath('code', 'CURRENT_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/current/general-data')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_standard_error_when_current_general_data_search_fails(): void
    {
        $user = $this->activeUser('Maria Silva');
        $journeyService = Mockery::mock(JourneyService::class);
        $journeyService->shouldReceive('findCurrentGeneralData')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyService::class, $journeyService);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel buscar os dados gerais da jornada no momento.')
            ->assertJsonPath('code', 'CURRENT_JOURNEY_GENERAL_DATA_SEARCH_FAILED')
            ->assertJsonPath('errors', []);
    }

    private function activeUser(string $name): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function journeyFor(Usuario $user, array $attributes = []): Journey
    {
        return Journey::create(array_merge([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-07-23 16:30:00',
            'DH_FIM' => null,
        ], $attributes));
    }

    private function englishLevel(string $namePt, string $nameEn, float $weight): Subcriterion
    {
        $criterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Ingles',
            'NO_CRITERIO_EN' => 'English',
        ]);

        return Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => $namePt,
            'NO_SUBCRITERIO_EN' => $nameEn,
            'NU_PESO_SUBCRITERIO' => $weight,
        ]);
    }

    private function experience(string $descriptionPt, string $descriptionEn): ProfessionalExperience
    {
        return ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => $descriptionPt,
            'DS_EXPERIENCIA_ATUACAO_EN' => $descriptionEn,
        ]);
    }
}
