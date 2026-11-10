<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserJourneyMetricsAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_lists_journeys_filtered_by_text_search(): void
    {
        $user = $this->activeUser('Carlos Silva', 2);
        $privacyDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy']);
        $securityDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança', 'NO_DOMINIO_CONHECIMENTO_EN' => 'Security']);

        $dpoProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado de Dados',
            'NO_PERFIS_EN' => 'Data Protection Officer',
            'DS_PERFIS_PT' => 'Gestao e governanca de dados',
        ]);

        $securityAnalystProfile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Analista de Segurança',
            'NO_PERFIS_EN' => 'Security Analyst',
            'DS_PERFIS_PT' => 'Protecao de redes e sistemas',
        ]);

        $journey1 = $this->journey($user, $dpoProfile, '2026-07-01 10:00:00', '2026-07-01 11:00:00', 80.0);
        $this->journey($user, $securityAnalystProfile, '2026-07-02 10:00:00', '2026-07-02 11:00:00', 90.0);

        // Busca por termo no nome em português sem acento
        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?search=encarregado")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journey1->PK_JORNADA)
            ->assertJsonPath('data.0.journey_profile.name', 'Encarregado de Dados');

        // Busca por termo em inglês
        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?search=Protection")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journey1->PK_JORNADA);

        // Busca por ID da jornada
        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?search={$journey1->PK_JORNADA}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journey1->PK_JORNADA);
    }

    public function test_user_lists_journeys_filtered_by_domain(): void
    {
        $user = $this->activeUser('Lucia Rocha', 2);
        $privacyDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $securityDomain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança']);

        $profilePrivacy = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Perfil Privacidade',
        ]);

        $profileSecurity = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Perfil Segurança',
        ]);

        $journeyPrivacy = $this->journey($user, $profilePrivacy, '2026-07-01 10:00:00');
        $this->journey($user, $profileSecurity, '2026-07-02 10:00:00');

        // Filtro por knowledge_domain_id individual
        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?knowledge_domain_id={$privacyDomain->PK_DOMINIO_CONHECIMENTO}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journeyPrivacy->PK_JORNADA)
            ->assertJsonPath('data.0.journey_profile.knowledge_domain.id', $privacyDomain->PK_DOMINIO_CONHECIMENTO);

        // Filtro por array domains
        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?domains[]={$privacyDomain->PK_DOMINIO_CONHECIMENTO}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $journeyPrivacy->PK_JORNADA);
    }

    public function test_user_lists_journeys_rejects_invalid_domain(): void
    {
        $user = $this->activeUser('Lucia Rocha', 2);

        $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys?knowledge_domain_id=99999")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => ['knowledge_domain_id'],
            ]);
    }

    public function test_user_lists_journeys_includes_domain_metrics_in_meta(): void
    {
        $user = $this->activeUser('Roberto Dias', 2);
        $domain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);

        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Perfil A',
        ]);

        $this->journey($user, $profile, '2026-07-01 10:00:00', '2026-07-01 11:00:00', 70.0);
        $this->journey($user, $profile, '2026-07-02 10:00:00', '2026-07-02 11:00:00', 90.0);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys")
            ->assertOk();

        $response
            ->assertJsonPath('meta.metrics_summary.total_journeys', 2)
            ->assertJsonPath('meta.metrics_summary.completed_journeys', 2)
            ->assertJsonPath('meta.metrics_summary.average_score', fn ($score) => (float) $score === 80.0)
            ->assertJsonPath('meta.metrics_summary.highest_score', fn ($score) => (float) $score === 90.0)
            ->assertJsonPath('meta.metrics_summary.latest_score', fn ($score) => (float) $score === 90.0);

        $domainMetrics = collect($response->json('meta.domain_metrics'))->firstWhere('domain_id', $domain->PK_DOMINIO_CONHECIMENTO);
        $this->assertNotNull($domainMetrics);
        $this->assertSame(2, $domainMetrics['total_journeys']);
        $this->assertSame(2, $domainMetrics['completed_journeys']);
        $this->assertSame(80.0, (float) $domainMetrics['average_score']);
    }

    public function test_dedicated_metrics_endpoint_returns_accurate_metrics_by_domain(): void
    {
        $user = $this->activeUser('Fernanda Costa', 2);

        $domain1 = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $domain2 = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Security',
        ]);

        $profile1 = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain1->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'DPO',
        ]);
        $profile2 = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain2->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'CISO',
        ]);

        $journey1 = $this->journey($user, $profile1, '2026-07-01 10:00:00', '2026-07-01 11:00:00', 85.0);
        $journey2 = $this->journey($user, $profile2, '2026-07-02 10:00:00'); // Ongoing

        $comp1 = Competency::create(['NO_COMPETENCIAS_PT' => 'LGPD', 'FK_DOMINIO_CONHECIMENTO' => $domain1->PK_DOMINIO_CONHECIMENTO]);
        $comp2 = Competency::create(['NO_COMPETENCIAS_PT' => 'DPIA', 'FK_DOMINIO_CONHECIMENTO' => $domain1->PK_DOMINIO_CONHECIMENTO]);

        AcquiredKnowledge::create(['FK_JORNADA' => $journey1->PK_JORNADA, 'FK_COMPETENCIAS' => $comp1->PK_COMPETENCIAS, 'TP_ADQUIRIDO' => 1]);
        AcquiredKnowledge::create(['FK_JORNADA' => $journey1->PK_JORNADA, 'FK_COMPETENCIAS' => $comp2->PK_COMPETENCIAS, 'TP_ADQUIRIDO' => 0]);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys/metrics")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Metricas por dominio recuperadas com sucesso.')
            ->assertJsonPath('data.user_id', $user->PK_USUARIO)
            ->assertJsonPath('data.summary.total_journeys', 2)
            ->assertJsonPath('data.summary.completed_journeys', 1)
            ->assertJsonPath('data.summary.ongoing_journeys', 1)
            ->assertJsonPath('data.summary.average_score', fn ($score) => (float) $score === 85.0);

        // Domínio 1 (Privacidade)
        $domainMetrics1 = collect($response->json('data.domains'))->firstWhere('domain_id', $domain1->PK_DOMINIO_CONHECIMENTO);
        $this->assertNotNull($domainMetrics1);
        $this->assertSame(1, $domainMetrics1['total_journeys']);
        $this->assertSame(1, $domainMetrics1['completed_journeys']);
        $this->assertSame(0, $domainMetrics1['ongoing_journeys']);
        $this->assertSame(85.0, (float) $domainMetrics1['average_score']);
        $this->assertSame(1, $domainMetrics1['acquired_competencies_count']);
        $this->assertSame(2, $domainMetrics1['total_competencies_evaluated']);
        $this->assertSame(50.0, (float) $domainMetrics1['competency_acquisition_rate']);

        // Domínio 2 (Segurança)
        $domainMetrics2 = collect($response->json('data.domains'))->firstWhere('domain_id', $domain2->PK_DOMINIO_CONHECIMENTO);
        $this->assertNotNull($domainMetrics2);
        $this->assertSame(1, $domainMetrics2['total_journeys']);
        $this->assertSame(0, $domainMetrics2['completed_journeys']);
        $this->assertSame(1, $domainMetrics2['ongoing_journeys']);
        $this->assertNull($domainMetrics2['average_score']);
    }

    public function test_administrator_can_view_metrics_of_any_user(): void
    {
        $admin = $this->activeUser('Admin Master', 1);
        $user = $this->activeUser('Usuario Comum', 2);

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys/metrics")
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->PK_USUARIO);
    }

    public function test_common_user_cannot_view_metrics_of_another_user(): void
    {
        $user1 = $this->activeUser('Usuario 1', 2);
        $user2 = $this->activeUser('Usuario 2', 2);

        $this->actingAs($user1)
            ->getJson("/api/users/{$user2->PK_USUARIO}/journeys/metrics")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');
    }

    public function test_user_without_results_view_all_permission_cannot_view_another_users_metrics_even_with_admin_type(): void
    {
        $userWithoutViewAll = Usuario::create([
            'FK_TIPO_PERFIL' => 3,
            'FK_TIPO_USUARIO' => 1,
            'NO_USUARIO' => 'Sem Permissao',
            'DS_EMAIL' => 'sem.permissao@example.com',
            'ST_ATIVO' => 1,
        ]);
        $targetUser = $this->activeUser('Alvo', 2);

        $this->actingAs($userWithoutViewAll)
            ->getJson("/api/users/{$targetUser->PK_USUARIO}/journeys/metrics")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');
    }

    public function test_manager_with_results_view_all_permission_can_view_any_user_metrics(): void
    {
        $manager = Usuario::create([
            'FK_TIPO_PERFIL' => 2,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Gestor',
            'DS_EMAIL' => 'gestor@example.com',
            'ST_ATIVO' => 1,
        ]);
        $targetUser = $this->activeUser('Alvo Gestor', 2);

        $this->actingAs($manager)
            ->getJson("/api/users/{$targetUser->PK_USUARIO}/journeys/metrics")
            ->assertOk()
            ->assertJsonPath('data.user_id', $targetUser->PK_USUARIO);
    }

    public function test_metrics_exclude_cancelled_journeys_from_completed_ongoing_and_scores(): void
    {
        $user = $this->activeUser('Usuario Cancelado', 2);
        $domain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Perfil Teste',
        ]);

        $this->journey($user, $profile, '2026-07-01 10:00:00', '2026-07-01 11:00:00', 80.0);
        Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'NU_PONTUACAO_TOTAL' => 100.0,
            'DH_CRIADO_EM' => '2026-07-02 10:00:00',
            'DH_FIM' => '2026-07-02 11:00:00',
            'DH_CANCELADA_EM' => '2026-07-02 12:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->PK_USUARIO}/journeys/metrics")
            ->assertOk();

        $response
            ->assertJsonPath('data.summary.total_journeys', 2)
            ->assertJsonPath('data.summary.completed_journeys', 1)
            ->assertJsonPath('data.summary.ongoing_journeys', 0)
            ->assertJsonPath('data.summary.average_score', fn ($score) => (float) $score === 80.0)
            ->assertJsonPath('data.summary.highest_score', fn ($score) => (float) $score === 80.0)
            ->assertJsonPath('data.summary.latest_score', fn ($score) => (float) $score === 80.0);
    }

    public function test_metrics_endpoint_rejects_unauthenticated_request(): void
    {
        $user = $this->activeUser('Usuario Anonimo', 2);

        $this->getJson("/api/users/{$user->PK_USUARIO}/journeys/metrics")
            ->assertUnauthorized();
    }

    public function test_metrics_endpoint_returns_not_found_for_unknown_user(): void
    {
        $admin = $this->activeUser('Admin Master', 1);

        $this->actingAs($admin)
            ->getJson('/api/users/999999/journeys/metrics')
            ->assertNotFound()
            ->assertJsonPath('code', 'USER_NOT_FOUND');
    }

    private function activeUser(string $name, int $userType): Usuario
    {
        return Usuario::create([
            'FK_TIPO_PERFIL' => $userType === 1 ? 1 : 3,
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function journey(
        Usuario $user,
        JourneyProfile $profile,
        string $startedAt,
        ?string $finishedAt = null,
        ?float $score = null,
    ): Journey {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'NU_PONTUACAO_TOTAL' => $score,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);
    }
}
