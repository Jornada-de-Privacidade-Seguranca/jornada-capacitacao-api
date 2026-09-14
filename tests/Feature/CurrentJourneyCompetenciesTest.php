<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Difficulty;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\ProfileCompetency;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentJourneyCompetenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_competency_options_for_the_current_journey_profile(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $otherProfile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $knowledgeArea = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Governanca',
            'NO_AREA_CONHECIMENTO_EN' => 'Governance',
        ]);
        $difficulty = Difficulty::create([
            'NO_DIFICULDADE_PT' => 'Basico',
            'NO_DIFICULDADE_EN' => 'Basic',
        ]);
        $firstCompetency = $this->createCompetency([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'FK_AREA_CONHECIMENTO' => $knowledgeArea->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_ORIGEM_COMPETENCIAS_PT' => 'Referencial',
            'NO_ORIGEM_COMPETENCIAS_EN' => 'Reference',
            'NO_COMPETENCIAS_PT' => 'LGPD',
            'NO_COMPETENCIAS_EN' => 'LGPD',
            'DS_COMPETENCIAS_PT' => 'Fundamentos da LGPD',
            'DS_COMPETENCIAS_EN' => 'LGPD fundamentals',
        ]);
        $secondCompetency = $this->createCompetency([
            'NO_COMPETENCIAS_PT' => 'Sem relacionamento',
            'NO_COMPETENCIAS_EN' => 'Without relationship',
        ]);
        $outsideCompetency = $this->createCompetency([
            'NO_COMPETENCIAS_PT' => 'Fora do perfil',
        ]);

        $this->attachCompetencyToProfile($profile, $secondCompetency);
        $this->attachCompetencyToProfile($profile, $firstCompetency);
        $this->attachCompetencyToProfile($otherProfile, $outsideCompetency);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Competencias disponiveis encontradas com sucesso.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.0.origin_pt', 'Referencial')
            ->assertJsonPath('data.0.origin_en', 'Reference')
            ->assertJsonPath('data.0.name_pt', 'LGPD')
            ->assertJsonPath('data.0.name_en', 'LGPD')
            ->assertJsonPath('data.0.description_pt', 'Fundamentos da LGPD')
            ->assertJsonPath('data.0.description_en', 'LGPD fundamentals')
            ->assertJsonPath('data.0.knowledge_domain.id', $knowledgeDomain->PK_DOMINIO_CONHECIMENTO)
            ->assertJsonPath('data.0.knowledge_domain.name_pt', 'Privacidade')
            ->assertJsonPath('data.0.knowledge_area.id', $knowledgeArea->PK_AREA_CONHECIMENTO)
            ->assertJsonPath('data.0.knowledge_area.name_en', 'Governance')
            ->assertJsonPath('data.0.difficulty.id', $difficulty->PK_DIFICULDADE)
            ->assertJsonPath('data.0.difficulty.name_en', 'Basic')
            ->assertJsonPath('data.1.id', $secondCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.1.knowledge_domain', null)
            ->assertJsonPath('data.1.knowledge_area', null)
            ->assertJsonPath('data.1.difficulty', null);
    }

    public function test_it_returns_empty_competencies_when_no_answers_are_saved(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);

        $this->attachCompetencyToProfile($profile, $this->createCompetency());

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Competencias da jornada encontradas com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(0, 'data.competencies');
    }

    public function test_it_returns_saved_incomplete_competency_answers(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $answeredCompetency = $this->createCompetency(['NO_COMPETENCIAS_PT' => 'Resposta salva']);
        $missingCompetency = $this->createCompetency(['NO_COMPETENCIAS_PT' => 'Ainda nao respondida']);
        $answer = $this->createAnswer($journey, $answeredCompetency, acquired: true);

        $this->attachCompetencyToProfile($profile, $answeredCompetency);
        $this->attachCompetencyToProfile($profile, $missingCompetency);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertOk()
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(1, 'data.competencies')
            ->assertJsonPath('data.competencies.0.id', $answer->PK_CONHECIMENTOS_ADQUIRIDOS)
            ->assertJsonPath('data.competencies.0.acquired', true)
            ->assertJsonPath('data.competencies.0.type_acquired', 1)
            ->assertJsonPath('data.competencies.0.competency.id', $answeredCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.competencies.0.competency.name_pt', 'Resposta salva');
    }

    public function test_it_marks_the_step_complete_when_all_profile_competencies_are_answered(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $firstCompetency = $this->createCompetency();
        $secondCompetency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $firstCompetency);
        $this->attachCompetencyToProfile($profile, $secondCompetency);
        $this->createAnswer($journey, $firstCompetency, acquired: true);
        $this->createAnswer($journey, $secondCompetency, acquired: false);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertOk()
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.competencies.1.acquired', false)
            ->assertJsonPath('data.competencies.1.type_acquired', 0);
    }

    public function test_it_replaces_previous_competency_answers(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $firstCompetency = $this->createCompetency();
        $secondCompetency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $firstCompetency);
        $this->attachCompetencyToProfile($profile, $secondCompetency);
        $oldAnswer = $this->createAnswer($journey, $firstCompetency, acquired: false);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $firstCompetency->PK_COMPETENCIAS, 'acquired' => true],
                    ['competency_id' => $firstCompetency->PK_COMPETENCIAS, 'acquired' => true],
                    ['competency_id' => $secondCompetency->PK_COMPETENCIAS, 'acquired' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Competencias da jornada encontradas com sucesso.')
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonCount(2, 'data.competencies')
            ->assertJsonPath('data.competencies.0.acquired', true)
            ->assertJsonPath('data.competencies.0.type_acquired', 1)
            ->assertJsonPath('data.competencies.1.acquired', false)
            ->assertJsonPath('data.competencies.1.type_acquired', 0);

        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'PK_CONHECIMENTOS_ADQUIRIDOS' => $oldAnswer->PK_CONHECIMENTOS_ADQUIRIDOS,
        ]);
        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $firstCompetency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
        ]);
        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $secondCompetency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 0,
        ]);
    }

    public function test_it_rejects_divergent_duplicate_competencies(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);
        $competency = $this->createCompetency();
        $this->attachCompetencyToProfile($profile, $competency);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $competency->PK_COMPETENCIAS, 'acquired' => true],
                    ['competency_id' => $competency->PK_COMPETENCIAS, 'acquired' => false],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['competencies']);
    }

    public function test_it_rejects_competency_outside_the_current_journey_profile(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $otherProfile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);
        $outsideCompetency = $this->createCompetency();
        $this->attachCompetencyToProfile($otherProfile, $outsideCompetency);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $outsideCompetency->PK_COMPETENCIAS, 'acquired' => true],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['competencies']);
    }

    public function test_it_rejects_nonexistent_competency(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => 999999, 'acquired' => true],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['competencies']);
    }

    public function test_it_returns_not_found_when_there_is_no_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: '2026-07-01 09:00:00');

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/journeys/current/competencies/options')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->getJson('/api/journeys/current/competencies')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_does_not_return_internal_calculation_fields(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $competency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $competency);
        $this->createAnswer($journey, $competency, acquired: true);

        $option = $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options')
            ->assertOk()
            ->json('data.0');
        $answer = $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertOk()
            ->json('data.competencies.0.competency');

        foreach (['weight', 'score', 'valor_ahp', 'subpontuacao', 'final_score', 'pontuacao_final'] as $field) {
            $this->assertArrayNotHasKey($field, $option);
            $this->assertArrayNotHasKey($field, $answer);
        }
    }

    public function test_it_requires_csrf_token_to_update_competency_answers(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);
        $competency = $this->createCompetency();
        $this->attachCompetencyToProfile($profile, $competency);

        $this->actingAs($user)
            ->putJson('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $competency->PK_COMPETENCIAS, 'acquired' => true],
                ],
            ])
            ->assertStatus(419)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
        ]);
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

    private function createCompetency(array $attributes = []): Competency
    {
        return Competency::create(array_merge([
            'NO_ORIGEM_COMPETENCIAS_PT' => 'Referencial',
            'NO_ORIGEM_COMPETENCIAS_EN' => 'Reference',
            'NO_COMPETENCIAS_PT' => 'LGPD',
            'NO_COMPETENCIAS_EN' => 'LGPD',
            'DS_COMPETENCIAS_PT' => 'Fundamentos da LGPD',
            'DS_COMPETENCIAS_EN' => 'LGPD fundamentals',
        ], $attributes));
    }

    private function attachCompetencyToProfile(JourneyProfile $profile, Competency $competency): void
    {
        ProfileCompetency::create([
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
        ]);
    }

    private function createAnswer(Journey $journey, Competency $competency, bool $acquired): AcquiredKnowledge
    {
        return AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => $acquired ? 1 : 0,
        ]);
    }
}
