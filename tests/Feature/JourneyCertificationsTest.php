<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyCertificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_selected_certifications_for_current_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);
        $certification = $this->createCertification('CIPM', 'IAPP');
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Certificacoes selecionadas encontradas com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonCount(1, 'data.certifications')
            ->assertJsonPath('data.certifications.0.id', $certification->PK_CERTIFICACAO_PROFISSIONAL)
            ->assertJsonPath('data.certifications.0.name', 'CIPM')
            ->assertJsonPath('data.certifications.0.issuer', 'IAPP')
            ->assertJsonPath('data.certifications.0.knowledge_domain.name_pt', 'Privacidade');
    }

    public function test_it_returns_empty_list_when_there_is_no_selection(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications')
            ->assertOk()
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.certifications', []);
    }

    public function test_it_replaces_selected_certifications(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);
        $oldCertification = $this->createCertification('CIPM', 'IAPP');
        $newCertification = $this->createCertification('CISSP', 'ISC2');
        $journey->professionalCertifications()->attach($oldCertification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->actingAs($user)
            ->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [$newCertification->PK_CERTIFICACAO_PROFISSIONAL],
            ])
            ->assertOk()
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonCount(1, 'data.certifications')
            ->assertJsonPath('data.certifications.0.id', $newCertification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->assertDatabaseMissing('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $oldCertification->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
        $this->assertDatabaseHas('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $newCertification->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
    }

    public function test_it_accepts_empty_list_for_optional_step(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);
        $certification = $this->createCertification('CIPM', 'IAPP');
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        $this->actingAs($user)
            ->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.certifications', []);

        $this->assertDatabaseMissing('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $certification->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
    }

    public function test_it_removes_duplicate_certification_ids_before_syncing(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);
        $certification = $this->createCertification('CIPM', 'IAPP');

        $this->actingAs($user)
            ->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [
                    $certification->PK_CERTIFICACAO_PROFISSIONAL,
                    $certification->PK_CERTIFICACAO_PROFISSIONAL,
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.certifications');

        $this->assertDatabaseCount('RL_CERTIFICACAO_JORNADA', 1);
        $this->assertDatabaseHas('RL_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CERTIFICACAO_PROFISSIONAL' => $certification->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
    }

    public function test_it_rejects_missing_certification_with_validation_error(): void
    {
        $user = $this->activeUser();
        $this->createOngoingJourney($user);

        $this->actingAs($user)
            ->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [999],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.certification_ids.0', 'A certificacao profissional informada nao existe.');
    }

    public function test_it_returns_not_found_when_there_is_no_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $this->createFinishedJourney($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications')
            ->assertNotFound()
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');

        $this->actingAs($user)
            ->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [],
            ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/journeys/current/certifications')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->withCsrfToken()
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [],
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_does_not_return_calculation_fields(): void
    {
        $user = $this->activeUser();
        $journey = $this->createOngoingJourney($user);
        $certification = $this->createCertification('CIPM', 'IAPP');
        $journey->professionalCertifications()->attach($certification->PK_CERTIFICACAO_PROFISSIONAL);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/certifications')
            ->assertOk();

        $certificationData = $response->json('data.certifications.0');

        $this->assertArrayNotHasKey('affinity', $certificationData);
        $this->assertArrayNotHasKey('weight', $certificationData);
        $this->assertArrayNotHasKey('ahp_value', $certificationData);
        $this->assertArrayNotHasKey('subscore', $certificationData);
        $this->assertArrayNotHasKey('final_score', $certificationData);
        $this->assertArrayNotHasKey('pontuacao', $certificationData);
    }

    public function test_it_requires_csrf_token_on_update(): void
    {
        $user = $this->activeUser();
        $this->createOngoingJourney($user);

        $this->actingAs($user)
            ->putJson('/api/journeys/current/certifications', [
                'certification_ids' => [],
            ])
            ->assertStatus(419)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Token CSRF invalido.')
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
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

    private function createOngoingJourney(Usuario $user): Journey
    {
        return $this->createJourney($user, null);
    }

    private function createFinishedJourney(Usuario $user): Journey
    {
        return $this->createJourney($user, '2026-07-01 09:00:00');
    }

    private function createJourney(Usuario $user, ?string $finishedAt): Journey
    {
        $journeyProfile = JourneyProfile::create([
            'NO_PERFIS_PT' => 'Privacidade',
            'NO_PERFIS_EN' => 'Privacy',
            'DS_PERFIS_PT' => 'Privacidade',
            'DS_PERFIS_EN' => 'Privacy',
        ]);

        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => '2026-07-01 08:00:00',
            'DH_FIM' => $finishedAt,
        ]);
    }

    private function createCertification(string $name, string $issuer): ProfessionalCertification
    {
        $knowledgeDomain = KnowledgeDomain::firstOrCreate(
            ['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade'],
            ['NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy'],
        );

        return ProfessionalCertification::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_CERTIFICACAO_PROFISSIONAL' => $name,
            'NO_EMISSOR_CERTIFICACAO' => $issuer,
        ]);
    }
}
