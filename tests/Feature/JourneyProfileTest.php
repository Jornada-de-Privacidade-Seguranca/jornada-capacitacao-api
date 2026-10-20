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
        JourneyProfile::create([
            'DS_PERFIS_PT' => 'Mock antigo',
            'DS_PERFIS_EN' => 'Old mock',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profiles retrieved successfully.')
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.name_pt', 'Encarregado pelo Tratamento de Dados Pessoais')
            ->assertJsonPath('meta.pagination.total', 21)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name_pt',
                        'name_en',
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

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?page=2&per_page=8')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 8)
            ->assertJsonPath('meta.pagination.total', 21)
            ->assertJsonPath('meta.pagination.last_page', 3)
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

    public function test_it_filters_journey_profiles_by_modality(): void
    {
        $user = $this->activeUser();
        $privacy = $this->profileInDomain('Privacidade', 'Privacy profile');
        $security = $this->profileInDomain('Segurança da Informação', 'Security profile');
        $joint = $this->profileInDomain('Conjunta (Privacidade/Segurança)', 'Joint profile');

        foreach ([
            'Privacidade' => [$privacy, [$security, $joint]],
            'Segurança' => [$security, [$privacy, $joint]],
            'Privacidade/Segurança' => [$joint, [$privacy, $security]],
        ] as $modality => [$expectedProfile, $excludedProfiles]) {
            $response = $this->actingAs($user)
                ->getJson('/api/journey-profiles?'.http_build_query(['modality' => $modality, 'per_page' => 100]))
                ->assertOk()
                ->assertJsonPath('success', true);

            $profileIds = collect($response->json('data'))->pluck('id');
            $this->assertContains($expectedProfile->PK_PERFIS_JORNADA, $profileIds);

            foreach ($excludedProfiles as $excludedProfile) {
                $this->assertNotContains($excludedProfile->PK_PERFIS_JORNADA, $profileIds);
            }
        }
    }

    public function test_it_preserves_pagination_when_filtering_by_modality(): void
    {
        $user = $this->activeUser();
        $this->profileInDomain('Privacidade', 'First privacy profile');
        $this->profileInDomain('Privacidade', 'Second privacy profile');
        $this->profileInDomain('Segurança da Informação', 'Security profile');

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query([
                'modality' => 'Privacidade',
                'page' => 2,
                'per_page' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 1);
    }

    public function test_it_rejects_invalid_modality(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?modality=Invalida')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.modality.0', 'A modalidade deve ser Privacidade, Segurança ou Privacidade/Segurança.');
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
            'NO_PERFIS_PT' => 'Perfil tecnico',
            'NO_PERFIS_EN' => 'Technical role',
            'DS_PERFIS_PT' => 'Perfil técnico',
            'DS_PERFIS_EN' => 'Technical profile',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles/'.$journeyProfile->PK_PERFIS_JORNADA)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profile retrieved successfully.')
            ->assertJsonPath('data.id', $journeyProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.name_pt', 'Perfil tecnico')
            ->assertJsonPath('data.name_en', 'Technical role')
            ->assertJsonPath('data.description_pt', 'Perfil técnico')
            ->assertJsonPath('data.description_en', 'Technical profile')
            ->assertJsonPath('data.type', $knowledgeDomain->PK_DOMINIO_CONHECIMENTO);
    }

    public function test_it_does_not_show_old_mocked_journey_profile_without_real_name(): void
    {
        $user = $this->activeUser();
        $journeyProfile = JourneyProfile::create([
            'DS_PERFIS_PT' => 'Mock antigo',
            'DS_PERFIS_EN' => 'Old mock',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles/'.$journeyProfile->PK_PERFIS_JORNADA)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Journey profile not found.')
            ->assertJsonPath('code', 'JOURNEY_PROFILE_NOT_FOUND');
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

        $this->getJson('/api/journey-profiles/'.$journeyProfile->PK_PERFIS_JORNADA)
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
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
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

    private function profileInDomain(string $domainName, string $profileName): JourneyProfile
    {
        $domain = KnowledgeDomain::query()->firstOrCreate(
            ['NO_DOMINIO_CONHECIMENTO_PT' => $domainName],
            ['NO_DOMINIO_CONHECIMENTO_EN' => $domainName],
        );

        return JourneyProfile::factory()->create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => $profileName,
        ]);
    }
}
