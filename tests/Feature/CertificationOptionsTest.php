<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_certification_options_for_a_user_with_an_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $journeyProfile = $this->createJourneyProfile();
        $this->createJourney($user, $journeyProfile, finishedAt: null);

        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Protecao de Dados',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Data Protection',
        ]);
        $withDomain = ProfessionalCertification::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_CERTIFICACAO_PROFISSIONAL' => 'CIPP/E',
            'NO_EMISSOR_CERTIFICACAO' => 'IAPP',
        ]);
        $withoutDomain = ProfessionalCertification::create([
            'NO_CERTIFICACAO_PROFISSIONAL' => 'CISSP',
            'NO_EMISSOR_CERTIFICACAO' => 'ISC2',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Certificacoes disponiveis encontradas com sucesso.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $withDomain->PK_CERTIFICACAO_PROFISSIONAL)
            ->assertJsonPath('data.0.name', 'CIPP/E')
            ->assertJsonPath('data.0.issuer', 'IAPP')
            ->assertJsonPath('data.0.knowledge_domain.id', $knowledgeDomain->PK_DOMINIO_CONHECIMENTO)
            ->assertJsonPath('data.0.knowledge_domain.name_pt', 'Protecao de Dados')
            ->assertJsonPath('data.1.id', $withoutDomain->PK_CERTIFICACAO_PROFISSIONAL)
            ->assertJsonPath('data.1.name', 'CISSP')
            ->assertJsonPath('data.1.issuer', 'ISC2')
            ->assertJsonPath('data.1.knowledge_domain', null)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'issuer',
                        'knowledge_domain',
                    ],
                ],
            ]);
    }

    public function test_it_does_not_return_affinity_weight_or_score_fields(): void
    {
        $user = $this->activeUser();
        $journeyProfile = $this->createJourneyProfile();
        $this->createJourney($user, $journeyProfile, finishedAt: null);

        ProfessionalCertification::create([
            'NO_CERTIFICACAO_PROFISSIONAL' => 'CISSP',
            'NO_EMISSOR_CERTIFICACAO' => 'ISC2',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications/options')
            ->assertOk();

        $certification = $response->json('data.0');

        $this->assertArrayNotHasKey('affinity', $certification);
        $this->assertArrayNotHasKey('weight', $certification);
        $this->assertArrayNotHasKey('score', $certification);
        $this->assertArrayNotHasKey('pontuacao', $certification);
    }

    public function test_it_returns_not_found_when_there_is_no_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $journeyProfile = $this->createJourneyProfile();
        $this->createJourney($user, $journeyProfile, finishedAt: '2026-07-01 09:00:00');

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications/options')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_user_has_no_journeys_at_all(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications/options')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/journeys/current/certifications/options')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
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

    private function createJourneyProfile(): JourneyProfile
    {
        return JourneyProfile::create([
            'NO_PERFIS_PT' => 'Privacidade',
            'NO_PERFIS_EN' => 'Privacy',
            'DS_PERFIS_PT' => 'Privacidade',
            'DS_PERFIS_EN' => 'Privacy',
        ]);
    }

    private function createJourney(Usuario $user, JourneyProfile $journeyProfile, ?string $finishedAt): Journey
    {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => '2026-07-01 08:00:00',
            'DH_FIM' => $finishedAt,
        ]);
    }
}
