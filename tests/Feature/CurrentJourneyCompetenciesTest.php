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

    public function test_it_filters_current_journey_competency_options_by_modality(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $privacyDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $securityDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança da Informação',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Information Security',
        ]);
        $jointDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Conjunta (Privacidade/Segurança)',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Joint (Privacy/security)',
        ]);

        $privacyCompetency = $this->createCompetency([
            'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_COMPETENCIAS_PT' => 'Competencia de privacidade',
            'NO_COMPETENCIAS_EN' => 'Privacy competency',
        ]);
        $securityCompetency = $this->createCompetency([
            'FK_DOMINIO_CONHECIMENTO' => $securityDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_COMPETENCIAS_PT' => 'Competencia de seguranca',
            'NO_COMPETENCIAS_EN' => 'Security competency',
        ]);
        $jointCompetency = $this->createCompetency([
            'FK_DOMINIO_CONHECIMENTO' => $jointDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_COMPETENCIAS_PT' => 'Competencia compartilhada',
            'NO_COMPETENCIAS_EN' => 'Joint competency',
        ]);
        $unclassifiedCompetency = $this->createCompetency([
            'NO_COMPETENCIAS_PT' => 'Competencia sem dominio',
            'NO_COMPETENCIAS_EN' => 'Competency without a domain',
        ]);

        foreach ([$privacyCompetency, $securityCompetency, $jointCompetency, $unclassifiedCompetency] as $competency) {
            $this->attachCompetencyToProfile($profile, $competency);
        }

        $privacyResponse = $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options?modality=Privacidade')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $privacyCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.0.name_pt', 'Competencia de privacidade')
            ->assertJsonPath('data.1.id', $jointCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.1.name_pt', 'Competencia compartilhada');
        $this->assertNotContains(
            $unclassifiedCompetency->PK_COMPETENCIAS,
            array_column($privacyResponse->json('data'), 'id'),
        );

        $securityResponse = $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options?modality=Segurança')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $securityCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.0.name_pt', 'Competencia de seguranca')
            ->assertJsonPath('data.1.id', $jointCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.1.name_pt', 'Competencia compartilhada');
        $this->assertNotContains(
            $unclassifiedCompetency->PK_COMPETENCIAS,
            array_column($securityResponse->json('data'), 'id'),
        );

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options?modality=Privacidade%2FSegurança')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.id', $privacyCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.0.name_pt', 'Competencia de privacidade')
            ->assertJsonPath('data.1.id', $securityCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.1.name_pt', 'Competencia de seguranca')
            ->assertJsonPath('data.2.id', $jointCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.2.name_pt', 'Competencia compartilhada')
            ->assertJsonPath('data.3.id', $unclassifiedCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.3.name_pt', 'Competencia sem dominio');

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.id', $privacyCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.1.id', $securityCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.2.id', $jointCompetency->PK_COMPETENCIAS)
            ->assertJsonPath('data.3.id', $unclassifiedCompetency->PK_COMPETENCIAS);
    }

    public function test_it_rejects_an_unsupported_modality_filter(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options?modality=education')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Modalidade informada e invalida.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['modality']);
    }

    public function test_it_validates_modality_before_checking_for_an_ongoing_journey(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options?modality=education')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Modalidade informada e invalida.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['modality']);
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
            ->assertJsonPath('message', 'Competencias da jornada salvas com sucesso.')
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

    public function test_it_accepts_empty_competency_answers_to_clear_the_step(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $competency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $competency);
        $this->createAnswer($journey, $competency, acquired: true);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(0, 'data.competencies');

        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
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

    public function test_it_rejects_invalid_payload_shapes(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        foreach ([[], ['competencies' => 'invalid']] as $payload) {
            $this->actingAs($user)
                ->putJsonWithCsrf('/api/journeys/current/competencies', $payload)
                ->assertUnprocessable()
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Validation error.')
                ->assertJsonPath('code', 'VALIDATION_ERROR')
                ->assertJsonValidationErrors(['competencies']);
        }
    }

    public function test_it_rejects_invalid_competency_items(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $invalidItems = [
            [[], ['competencies.0.competency_id', 'competencies.0.acquired']],
            [['competency_id' => 0, 'acquired' => true], ['competencies.0.competency_id']],
            [['competency_id' => 'invalid', 'acquired' => true], ['competencies.0.competency_id']],
            [['competency_id' => 1, 'acquired' => 'invalid'], ['competencies.0.acquired']],
        ];

        foreach ($invalidItems as [$item, $expectedErrors]) {
            $this->actingAs($user)
                ->putJsonWithCsrf('/api/journeys/current/competencies', ['competencies' => [$item]])
                ->assertUnprocessable()
                ->assertJsonPath('code', 'VALIDATION_ERROR')
                ->assertJsonValidationErrors($expectedErrors);
        }
    }

    public function test_it_rejects_more_than_five_hundred_competencies(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => array_fill(0, 501, [
                    'competency_id' => 1,
                    'acquired' => true,
                ]),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['competencies']);
    }

    public function test_it_keeps_previous_answers_when_the_replacement_is_invalid(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $savedCompetency = $this->createCompetency();
        $validCompetency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $savedCompetency);
        $this->attachCompetencyToProfile($profile, $validCompetency);
        $savedAnswer = $this->createAnswer($journey, $savedCompetency, acquired: true);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $validCompetency->PK_COMPETENCIAS, 'acquired' => false],
                    ['competency_id' => 999999, 'acquired' => true],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'PK_CONHECIMENTOS_ADQUIRIDOS' => $savedAnswer->PK_CONHECIMENTOS_ADQUIRIDOS,
            'TP_ADQUIRIDO' => 1,
        ]);
        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $validCompetency->PK_COMPETENCIAS,
        ]);
    }

    public function test_it_updates_only_the_authenticated_users_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $ongoingJourney = $this->createJourney($user, $profile, finishedAt: null);
        $finishedJourney = $this->createJourney($user, $profile, finishedAt: '2026-06-01 09:00:00');
        $otherJourney = $this->createJourney($otherUser, $profile, finishedAt: null);
        $competency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $competency);
        $finishedAnswer = $this->createAnswer($finishedJourney, $competency, acquired: false);
        $otherAnswer = $this->createAnswer($otherJourney, $competency, acquired: false);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $competency->PK_COMPETENCIAS, 'acquired' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.journey_id', $ongoingJourney->PK_JORNADA);

        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'PK_CONHECIMENTOS_ADQUIRIDOS' => $finishedAnswer->PK_CONHECIMENTOS_ADQUIRIDOS,
            'TP_ADQUIRIDO' => 0,
        ]);
        $this->assertDatabaseHas('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'PK_CONHECIMENTOS_ADQUIRIDOS' => $otherAnswer->PK_CONHECIMENTOS_ADQUIRIDOS,
            'TP_ADQUIRIDO' => 0,
        ]);
    }

    public function test_it_ignores_saved_answers_that_no_longer_belong_to_the_profile(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $journey = $this->createJourney($user, $profile, finishedAt: null);
        $profileCompetency = $this->createCompetency();
        $outsideCompetency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $profileCompetency);
        $this->createAnswer($journey, $outsideCompetency, acquired: true);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies')
            ->assertOk()
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(0, 'data.competencies');
    }

    public function test_it_keeps_the_step_incomplete_after_a_partial_update(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);
        $firstCompetency = $this->createCompetency();
        $secondCompetency = $this->createCompetency();

        $this->attachCompetencyToProfile($profile, $firstCompetency);
        $this->attachCompetencyToProfile($profile, $secondCompetency);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $firstCompetency->PK_COMPETENCIAS, 'acquired' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(1, 'data.competencies');
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

    public function test_options_and_update_return_not_found_when_there_is_no_ongoing_journey(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $finishedJourney = $this->createJourney($user, $profile, finishedAt: '2026-07-01 09:00:00');
        $competency = $this->createCompetency();
        $this->attachCompetencyToProfile($profile, $competency);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/competencies/options')
            ->assertNotFound()
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/competencies', [
                'competencies' => [
                    ['competency_id' => $competency->PK_COMPETENCIAS, 'acquired' => true],
                ],
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('TB_CONHECIMENTOS_ADQUIRIDOS', [
            'FK_JORNADA' => $finishedJourney->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
        ]);
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

        $this->putJsonWithCsrf('/api/journeys/current/competencies', ['competencies' => []])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
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

    public function test_it_returns_knowledge_area_descriptions_in_competency_options(): void
    {
        $user = $this->activeUser();
        $profile = $this->createJourneyProfile();
        $this->createJourney($user, $profile, finishedAt: null);

        $describedArea = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Governanca',
            'NO_AREA_CONHECIMENTO_EN' => 'Governance',
            'DS_AREA_CONHECIMENTO_PT' => 'Conhecimentos de governanca de dados.',
            'DS_AREA_CONHECIMENTO_EN' => 'Data governance knowledge.',
        ]);
        $areaWithoutDescription = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Sem descricao',
            'NO_AREA_CONHECIMENTO_EN' => 'No description',
        ]);
        $areaWithEmptyDescription = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Descricao vazia',
            'NO_AREA_CONHECIMENTO_EN' => 'Empty description',
            'DS_AREA_CONHECIMENTO_PT' => '',
            'DS_AREA_CONHECIMENTO_EN' => '',
        ]);

        foreach ([$describedArea, $areaWithoutDescription, $areaWithEmptyDescription] as $area) {
            $this->attachCompetencyToProfile($profile, $this->createCompetency([
                'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
                'NO_COMPETENCIAS_PT' => 'Competencia de '.$area->NO_AREA_CONHECIMENTO_PT,
            ]));
        }

        $areas = collect(
            $this->actingAs($user)
                ->getJson('/api/journeys/current/competencies/options')
                ->assertOk()
                ->json('data')
        )->pluck('knowledge_area')->keyBy('id');

        $this->assertSame('Conhecimentos de governanca de dados.', $areas[$describedArea->PK_AREA_CONHECIMENTO]['description_pt']);
        $this->assertSame('Data governance knowledge.', $areas[$describedArea->PK_AREA_CONHECIMENTO]['description_en']);

        foreach ([$areaWithoutDescription, $areaWithEmptyDescription] as $area) {
            $this->assertArrayHasKey('description_pt', $areas[$area->PK_AREA_CONHECIMENTO]);
            $this->assertNull($areas[$area->PK_AREA_CONHECIMENTO]['description_pt']);
            $this->assertNull($areas[$area->PK_AREA_CONHECIMENTO]['description_en']);
        }
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
