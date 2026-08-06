<?php

namespace Tests\Feature;

use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_journey_profiles(): void
    {
        $user = $this->activeUser();
        JourneyProfile::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profiles retrieved successfully.')
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'description_pt',
                        'description_en',
                        'type',
                    ],
                ],
            ]);
    }

    public function test_it_paginates_journey_profiles(): void
    {
        $user = $this->activeUser();
        JourneyProfile::factory()->count(25)->create();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?page=2&per_page=8')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 8)
            ->assertJsonPath('meta.pagination.total', 25)
            ->assertJsonPath('meta.pagination.last_page', 4)
            ->assertJsonPath('meta.pagination.has_next_page', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'has_next_page',
                    ],
                ],
            ]);
    }

    public function test_it_shows_a_journey_profile(): void
    {
        $user = $this->activeUser();
        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade e Seguranca',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy and Security',
        ]);
        $journeyProfile = JourneyProfile::factory()->create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'DS_PERFIS_PT' => 'Perfil técnico',
            'DS_PERFIS_EN' => 'Technical profile',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles/' . $journeyProfile->PK_PERFIS_JORNADA)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profile retrieved successfully.')
            ->assertJsonPath('data.id', $journeyProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.description_pt', 'Perfil técnico')
            ->assertJsonPath('data.description_en', 'Technical profile')
            ->assertJsonPath('data.type', $knowledgeDomain->PK_DOMINIO_CONHECIMENTO);
    }

    public function test_it_returns_not_found_when_journey_profile_does_not_exist(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Journey profile not found.')
            ->assertJsonPath('code', 'JOURNEY_PROFILE_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_list_request(): void
    {
        $this->getJson('/api/journey-profiles')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_show_request(): void
    {
        $journeyProfile = JourneyProfile::factory()->create();

        $this->getJson('/api/journey-profiles/' . $journeyProfile->PK_PERFIS_JORNADA)
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_invalid_pagination_parameters(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?page=0&per_page=101')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Parametros de paginacao invalidos.')
            ->assertJsonPath('code', 'INVALID_PAGINATION_PARAMETERS')
            ->assertJsonPath('errors.page.0', 'A pagina atual deve ser maior ou igual a 1.')
            ->assertJsonPath('errors.per_page.0', 'A quantidade de registros por pagina nao pode ser maior que 100.');
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
}
