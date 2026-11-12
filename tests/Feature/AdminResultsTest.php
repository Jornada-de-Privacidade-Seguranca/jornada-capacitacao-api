<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_can_list_completed_results_ordered_by_finished_at_with_pagination(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1, 'TI', 1);
        $manager = $this->activeUser('Gestor Area', 2, 'TI', 2);
        $user1 = $this->activeUser('João Silva', 3, 'Privacidade', 2);
        $user2 = $this->activeUser('Maria Souza', 3, 'Segurança', 2);

        $privacyDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $securityDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Security',
        ]);

        $dpoProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado de Dados',
            'NO_PERFIS_EN' => 'Data Protection Officer',
        ]);
        $analystProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Analista de Segurança',
            'NO_PERFIS_EN' => 'Security Analyst',
        ]);

        $older = $this->journey($user1, $dpoProfile, '2026-07-01 09:00:00', '2026-07-01 11:00:00', 75.5);
        $newer = $this->journey($user2, $analystProfile, '2026-07-02 09:00:00', '2026-07-02 15:00:00', 92.0);
        // Em andamento e cancelada nao devem aparecer
        $this->journey($user1, $dpoProfile, '2026-07-03 09:00:00', null, null);
        $this->journey($user2, $analystProfile, '2026-07-04 09:00:00', '2026-07-04 10:00:00', 88.0, '2026-07-04 11:00:00');

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/results?page=1&per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $newer->PK_JORNADA)
            ->assertJsonPath('data.0.user.id', $user2->PK_USUARIO)
            ->assertJsonPath('data.0.user.name', 'Maria Souza')
            ->assertJsonPath('data.0.user.email', 'maria.souza@example.com')
            ->assertJsonPath('data.0.user.department', 'Segurança')
            ->assertJsonPath('data.0.journey_profile.id', $analystProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.0.journey_profile.name_pt', 'Analista de Segurança')
            ->assertJsonPath('data.0.journey_profile.name_en', 'Security Analyst')
            ->assertJsonPath('data.0.knowledge_domain.id', $securityDomain->PK_DOMINIO_CONHECIMENTO)
            ->assertJsonPath('data.0.knowledge_domain.name_pt', 'Segurança')
            ->assertJsonPath('data.0.knowledge_domain.name_en', 'Security')
            ->assertJsonPath('data.0.finished_at', '2026-07-02 15:00:00')
            ->assertJsonPath('data.0.score', fn ($score) => (float) $score === 92.0)
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('meta.pagination.has_next_page', true);

        $this->actingAs($manager)
            ->getJson('/api/admin/results?page=2&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->PK_JORNADA);
    }

    public function test_admin_results_supports_all_filters_and_combinations(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1, 'TI', 1);
        $user1 = $this->activeUser('João Ávila', 3, 'Privacidade', 2);
        $user2 = $this->activeUser('Ana Costa', 3, 'Infraestrutura', 2);

        $privacyDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy']);
        $securityDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Security']);

        $dpoProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado de Dados',
            'NO_PERFIS_EN' => 'Data Protection Officer',
        ]);
        $secProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Analista de Segurança',
            'NO_PERFIS_EN' => 'Security Analyst',
        ]);

        $j1 = $this->journey($user1, $dpoProfile, '2026-07-01 09:00:00', '2026-07-05 10:00:00', 80.0);
        $j2 = $this->journey($user1, $secProfile, '2026-07-10 09:00:00', '2026-07-15 10:00:00', 90.0);
        $j3 = $this->journey($user2, $dpoProfile, '2026-07-20 09:00:00', '2026-07-25 10:00:00', 70.0);

        // search por nome sem acento
        $this->actingAs($admin)
            ->getJson('/api/admin/results?search=avila')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // search por email
        $this->actingAs($admin)
            ->getJson('/api/admin/results?search=ana.costa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j3->PK_JORNADA);

        // search por perfil EN
        $this->actingAs($admin)
            ->getJson('/api/admin/results?search=Security+Analyst')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j2->PK_JORNADA);

        // user_id
        $this->actingAs($admin)
            ->getJson("/api/admin/results?user_id={$user2->PK_USUARIO}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j3->PK_JORNADA);

        // department
        $this->actingAs($admin)
            ->getJson('/api/admin/results?department=Infraestrutura')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j3->PK_JORNADA);

        // domains[]
        $this->actingAs($admin)
            ->getJson("/api/admin/results?domains[]={$privacyDomain->PK_DOMINIO_CONHECIMENTO}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // journey_profile_id
        $this->actingAs($admin)
            ->getJson("/api/admin/results?journey_profile_id={$secProfile->PK_PERFIS_JORNADA}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j2->PK_JORNADA);

        // ended_from & ended_to
        $this->actingAs($admin)
            ->getJson('/api/admin/results?ended_from=2026-07-10&ended_to=2026-07-20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j2->PK_JORNADA);

        // combinacao de filtros
        $this->actingAs($admin)
            ->getJson("/api/admin/results?search=joao&department=Privacidade&domains[]={$privacyDomain->PK_DOMINIO_CONHECIMENTO}&ended_from=2026-07-01&ended_to=2026-07-10")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $j1->PK_JORNADA);
    }

    public function test_admin_results_metrics_returns_domain_averages_without_overall_average_and_respects_filters(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1, 'TI', 1);
        $user1 = $this->activeUser('Usuario Um', 3, 'Privacidade', 2);
        $user2 = $this->activeUser('Usuario Dois', 3, 'Segurança', 2);

        $privacyDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy']);
        $securityDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Security']);
        $bothDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade e Segurança', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy and Security']);

        // Sem jornadas concluidas
        $emptyResponse = $this->actingAs($admin)
            ->getJson('/api/admin/results/metrics')
            ->assertOk()
            ->assertJsonPath('data.total_completed_journeys', 0)
            ->assertJsonPath('data.distinct_users_count', 0)
            ->assertJsonCount(KnowledgeDomain::query()->count(), 'data.domains')
            ->assertJsonMissingPath('data.average_score');

        $emptyDomains = collect($emptyResponse->json('data.domains'));
        $this->assertNull($emptyDomains->firstWhere('domain_id', $privacyDomain->PK_DOMINIO_CONHECIMENTO)['average_score']);

        $dpoProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'DPO',
        ]);
        $secProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'SecOps',
        ]);

        $this->journey($user1, $dpoProfile, '2026-07-01 09:00:00', '2026-07-01 10:00:00', 80.0);
        $this->journey($user1, $dpoProfile, '2026-07-02 09:00:00', '2026-07-02 10:00:00', 100.0);
        $this->journey($user2, $secProfile, '2026-07-03 09:00:00', '2026-07-03 10:00:00', 70.0);
        // Em andamento nao entra
        $this->journey($user2, $secProfile, '2026-07-04 09:00:00', null, null);

        $fullMetrics = $this->actingAs($admin)
            ->getJson('/api/admin/results/metrics')
            ->assertOk()
            ->assertJsonPath('data.total_completed_journeys', 3)
            ->assertJsonPath('data.distinct_users_count', 2)
            ->assertJsonMissingPath('data.average_score');

        $domains = collect($fullMetrics->json('data.domains'));
        $privacyStat = $domains->firstWhere('domain_id', $privacyDomain->PK_DOMINIO_CONHECIMENTO);
        $securityStat = $domains->firstWhere('domain_id', $securityDomain->PK_DOMINIO_CONHECIMENTO);
        $bothStat = $domains->firstWhere('domain_id', $bothDomain->PK_DOMINIO_CONHECIMENTO);

        $this->assertSame(2, $privacyStat['completed_journeys']);
        $this->assertSame(90.0, (float) $privacyStat['average_score']);
        $this->assertSame(1, $securityStat['completed_journeys']);
        $this->assertSame(70.0, (float) $securityStat['average_score']);
        $this->assertSame(0, $bothStat['completed_journeys']);
        $this->assertNull($bothStat['average_score']);

        // Metricas respeitando filtro por setor
        $this->actingAs($admin)
            ->getJson('/api/admin/results/metrics?department=Privacidade')
            ->assertOk()
            ->assertJsonPath('data.total_completed_journeys', 2)
            ->assertJsonPath('data.distinct_users_count', 1);
    }

    public function test_auxiliary_endpoints_return_departments_and_users_with_completed_results(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1, 'TI', 1);
        $userCompleted = $this->activeUser('Carlos Alberto', 3, 'Juridico', 2);
        $userOngoingOnly = $this->activeUser('Beatriz Lima', 3, 'Compliance', 2);
        $this->activeUser('Sem Setor', 3, null, 2);

        $domain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'DPO',
        ]);

        $this->journey($userCompleted, $profile, '2026-07-01 09:00:00', '2026-07-01 10:00:00', 85.0);
        $this->journey($userOngoingOnly, $profile, '2026-07-02 09:00:00', null, null);

        $this->actingAs($admin)
            ->getJson('/api/users/departments')
            ->assertOk()
            ->assertJsonPath('data', ['Administracao', 'Compliance', 'Juridico', 'TI', 'Testes']);

        $this->actingAs($admin)
            ->getJson('/api/users/with-results')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userCompleted->PK_USUARIO)
            ->assertJsonPath('data.0.name', 'Carlos Alberto')
            ->assertJsonPath('data.0.email', 'carlos.alberto@example.com');
    }

    public function test_admin_results_endpoints_enforce_authentication_and_results_view_all_permission(): void
    {
        $commonUser = $this->activeUser('Usuario Comum', 3, 'TI', 2);
        $manager = $this->activeUser('Gestor Area', 2, 'TI', 2);

        $endpoints = [
            '/api/admin/results',
            '/api/admin/results/metrics',
            '/api/users/departments',
            '/api/users/with-results',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)
                ->assertUnauthorized()
                ->assertJsonPath('code', 'UNAUTHENTICATED');
        }

        foreach ($endpoints as $endpoint) {
            $this->actingAs($commonUser)
                ->getJson($endpoint)
                ->assertForbidden()
                ->assertJsonPath('code', 'PERMISSION_DENIED');

            $this->actingAs($manager)
                ->getJson($endpoint)
                ->assertOk();
        }
    }

    public function test_admin_results_validation_rejects_invalid_parameters(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1, 'TI', 1);

        $this->actingAs($admin)
            ->getJson('/api/admin/results?per_page=150&user_id=999999&ended_from=2026-07-10&ended_to=2026-07-01')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => ['per_page', 'user_id', 'ended_to'],
            ]);
    }

    private function activeUser(string $name, int $profileType, ?string $department, int $userType): Usuario
    {
        $slug = str_replace([' ', 'á', 'ã', 'ç', 'é', 'í', 'ó', 'ú'], ['.', 'a', 'a', 'c', 'e', 'i', 'o', 'u'], mb_strtolower($name));

        return Usuario::create([
            'FK_TIPO_PERFIL' => $profileType,
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'NO_SETOR' => $department,
            'DS_EMAIL' => "{$slug}@example.com",
            'ST_ATIVO' => 1,
        ]);
    }

    private function journey(
        Usuario $user,
        JourneyProfile $profile,
        string $startedAt,
        ?string $finishedAt,
        ?float $score,
        ?string $cancelledAt = null,
    ): Journey {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'NU_PONTUACAO_TOTAL' => $score,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
            'DH_CANCELADA_EM' => $cancelledAt,
        ]);
    }
}
