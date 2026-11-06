<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\ProfileCompetency;
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
            ])
            ->assertJsonMissingPath('data.0.competencies_count')
            ->assertJsonMissingPath('data.0.knowledge_areas_count');
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

    public function test_it_searches_journey_profile_names_and_descriptions_without_case_or_accent_sensitivity(): void
    {
        $user = $this->activeUser();
        $profile = JourneyProfile::factory()->create([
            'NO_PERFIS_PT' => 'Proteção Codex 123XYZ',
            'NO_PERFIS_EN' => 'Information Security Codex 123XYZ',
            'DS_PERFIS_PT' => 'Especialista em Governança Codex 123XYZ',
            'DS_PERFIS_EN' => 'Privacy program owner Codex 123XYZ',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?search=PROTECAO%20CODEX%20123XYZ')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $profile->PK_PERFIS_JORNADA);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?search=information%20security%20codex%20123xyz')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $profile->PK_PERFIS_JORNADA);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?search=governanca%20codex%20123xyz')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $profile->PK_PERFIS_JORNADA);
    }

    public function test_it_uses_the_same_accent_normalization_for_search_terms_and_profile_text(): void
    {
        $user = $this->activeUser();
        $profile = JourneyProfile::factory()->create([
            'NO_PERFIS_PT' => 'Especialista em Straße e governança',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query(['search' => 'STRAßE']))
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $profile->PK_PERFIS_JORNADA);
    }

    public function test_it_returns_no_journey_profiles_when_search_has_no_matches(): void
    {
        $this->actingAs($this->activeUser())
            ->getJson('/api/journey-profiles?search=nao-existe-este-perfil')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.pagination.total', 0)
            ->assertJsonPath('meta.pagination.last_page', 1)
            ->assertJsonPath('meta.pagination.has_next_page', false);
    }

    public function test_it_treats_like_wildcards_as_literal_search_characters(): void
    {
        $user = $this->activeUser();
        $literalPercent = JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex 100% literal']);
        JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex 1000 literal']);
        $literalUnderscore = JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex A_B literal']);
        JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex A-B literal']);
        $literalBackslash = JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex back\\slash literal']);
        JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Perfil Codex backslash literal']);

        foreach ([
            ['search' => '%', 'id' => $literalPercent->PK_PERFIS_JORNADA],
            ['search' => '_', 'id' => $literalUnderscore->PK_PERFIS_JORNADA],
            ['search' => '\\', 'id' => $literalBackslash->PK_PERFIS_JORNADA],
        ] as $case) {
            $this->actingAs($user)
                ->getJson('/api/journey-profiles?'.http_build_query(['search' => $case['search']]))
                ->assertOk()
                ->assertJsonPath('meta.pagination.total', 1)
                ->assertJsonPath('data.0.id', $case['id']);
        }
    }

    public function test_it_combines_search_with_modality_filter(): void
    {
        $user = $this->activeUser();
        $privacy = $this->profileInDomain('Privacidade', 'Codex123XYZ privacy profile');
        $security = $this->profileInDomain('Segurança da Informação', 'Codex123XYZ security profile');

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query([
                'search' => 'Codex123XYZ',
                'modality' => 'Privacidade',
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $privacy->PK_PERFIS_JORNADA)
            ->assertJsonMissing(['id' => $security->PK_PERFIS_JORNADA]);
    }

    public function test_it_paginates_only_journey_profiles_matching_search(): void
    {
        $user = $this->activeUser();
        $profiles = collect([
            'Analista de CodexRisco123XYZ',
            'Consultor de CodexRisco123XYZ',
            'Gestor de CodexRisco123XYZ',
        ])->map(fn (string $name): JourneyProfile => JourneyProfile::factory()->create([
            'NO_PERFIS_PT' => $name,
        ]));
        JourneyProfile::factory()->create(['NO_PERFIS_PT' => 'Analista de Privacidade']);

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query([
                'search' => 'codexrisco123xyz',
                'page' => 2,
                'per_page' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $profiles[1]->PK_PERFIS_JORNADA)
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonPath('meta.pagination.last_page', 3)
            ->assertJsonPath('meta.pagination.has_next_page', true);
    }

    public function test_it_rejects_non_string_search(): void
    {
        $this->actingAs($this->activeUser())
            ->getJson('/api/journey-profiles?search%5B%5D=perfil')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.search.0', 'A busca deve ser um texto.');
    }

    public function test_it_filters_journey_profiles_by_modality(): void
    {
        $user = $this->activeUser();
        $privacy = $this->profileInDomain('Privacidade', 'Privacy profile');
        $security = $this->profileInDomain('Segurança da Informação', 'Security profile');
        $joint = $this->profileInDomain('Conjunta (Privacidade/Segurança)', 'Joint profile');

        $privacyIds = $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query(['modality' => 'Privacidade', 'per_page' => 100]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.*.id');

        $this->assertContains($privacy->PK_PERFIS_JORNADA, $privacyIds);
        $this->assertContains($joint->PK_PERFIS_JORNADA, $privacyIds);
        $this->assertNotContains($security->PK_PERFIS_JORNADA, $privacyIds);

        $securityIds = $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query(['modality' => 'Segurança', 'per_page' => 100]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.*.id');

        $this->assertContains($security->PK_PERFIS_JORNADA, $securityIds);
        $this->assertContains($joint->PK_PERFIS_JORNADA, $securityIds);
        $this->assertNotContains($privacy->PK_PERFIS_JORNADA, $securityIds);

        $combinedIds = $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query([
                'modality' => 'Privacidade/Segurança',
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.*.id');

        $this->assertContains($privacy->PK_PERFIS_JORNADA, $combinedIds);
        $this->assertContains($security->PK_PERFIS_JORNADA, $combinedIds);
        $this->assertContains($joint->PK_PERFIS_JORNADA, $combinedIds);
    }

    public function test_it_preserves_pagination_when_filtering_by_modality(): void
    {
        $user = $this->activeUser();
        $this->profileInDomain('Privacidade', 'First privacy profile');
        $this->profileInDomain('Conjunta (Privacidade/Segurança)', 'Joint profile');
        $this->profileInDomain('Segurança da Informação', 'Security profile');

        $expectedIds = JourneyProfile::query()
            ->whereIn('FK_DOMINIO_CONHECIMENTO', [1, 3])
            ->whereNotNull('NO_PERFIS_PT')
            ->orderBy('PK_PERFIS_JORNADA')
            ->pluck('PK_PERFIS_JORNADA')
            ->all();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?'.http_build_query([
                'modality' => 'Privacidade',
                'page' => 2,
                'per_page' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.total', count($expectedIds))
            ->assertJsonPath('meta.pagination.last_page', count($expectedIds))
            ->assertJsonPath('data.0.id', $expectedIds[1]);
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

    public function test_it_rejects_non_string_modality(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journey-profiles?modality%5B%5D=Privacidade')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.modality.0', 'A modalidade deve ser um texto.');
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
            ->assertJsonPath('data.type', $knowledgeDomain->PK_DOMINIO_CONHECIMENTO)
            ->assertJsonPath('data.competencies_count', 0)
            ->assertJsonPath('data.knowledge_areas_count', 0);
    }

    public function test_it_returns_competency_and_distinct_knowledge_area_counts_for_a_journey_profile(): void
    {
        $user = $this->activeUser();
        $profile = JourneyProfile::factory()->create();
        $privacyArea = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Gestao de Privacidade']);
        $securityArea = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Gestao de Seguranca']);

        $competencies = [
            Competency::create([
                'FK_AREA_CONHECIMENTO' => $privacyArea->PK_AREA_CONHECIMENTO,
                'NO_COMPETENCIAS_PT' => 'Competencia de privacidade 1',
            ]),
            Competency::create([
                'FK_AREA_CONHECIMENTO' => $privacyArea->PK_AREA_CONHECIMENTO,
                'NO_COMPETENCIAS_PT' => 'Competencia de privacidade 2',
            ]),
            Competency::create([
                'FK_AREA_CONHECIMENTO' => $securityArea->PK_AREA_CONHECIMENTO,
                'NO_COMPETENCIAS_PT' => 'Competencia de seguranca',
            ]),
            Competency::create([
                'FK_AREA_CONHECIMENTO' => null,
                'NO_COMPETENCIAS_PT' => 'Competencia sem area',
            ]),
        ];

        foreach ($competencies as $competency) {
            ProfileCompetency::create([
                'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
                'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            ]);
        }

        $this->actingAs($user)
            ->getJson('/api/journey-profiles/'.$profile->PK_PERFIS_JORNADA)
            ->assertOk()
            ->assertJsonPath('data.competencies_count', 4)
            ->assertJsonPath('data.knowledge_areas_count', 3);
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
            'FK_TIPO_PERFIL' => 3,
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
