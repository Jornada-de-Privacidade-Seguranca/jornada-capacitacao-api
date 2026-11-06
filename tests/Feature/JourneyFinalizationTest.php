<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\CompetencyCriterionWeight;
use App\Models\Criterion;
use App\Models\Difficulty;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\KnowledgeArea;
use App\Models\ProfessionalExperience;
use App\Models\ProfileCompetency;
use App\Models\Subcriterion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class JourneyFinalizationTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_finalizes_the_current_journey_with_legacy_answers_and_keeps_the_breakdown(): void
    {
        Carbon::setTestNow('2026-09-28 14:30:00');
        [$user, $journey] = $this->completedAnswersFixture();

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Jornada finalizada com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.finished_at', '2026-09-28 14:30:00')
            ->assertJsonMissingPath('data.total_score');

        $detail = $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            // A nota total vem só das competências: a única do perfil foi declarada
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.score_breakdown.0.category', 'professional_experience')
            ->assertJsonPath('data.score_breakdown.0.name_pt', 'Tempo de Experiência Profissional')
            ->assertJsonPath('data.score_breakdown.0.score', 20)
            ->assertJsonPath('data.score_breakdown.1.category', 'profile_experience')
            ->assertJsonPath('data.score_breakdown.1.score', 50)
            ->assertJsonPath('data.score_breakdown.2.category', 'english_proficiency')
            ->assertJsonPath('data.score_breakdown.2.score', 41.131)
            ->assertJsonPath('data.score_breakdown.3.score', 0)
            ->assertJsonPath('data.score_breakdown.4.score', 0)
            ->assertJsonPath('data.score_breakdown.5.category', 'competencies')
            ->assertJsonPath('data.score_breakdown.5.score', 10);

        $this->assertEquals($journey->fresh()->JS_SCORE_BREAKDOWN, $detail->json('data.score_breakdown'));

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 100,
            'DH_FIM' => '2026-09-28 14:30:00',
        ]);
        $this->assertDatabaseCount('RL_CURSOS_SUPERIORES_JORNADA', 0);
        $this->assertDatabaseCount('RL_CERTIFICACAO_JORNADA', 0);
    }

    public function test_it_persists_normalized_scores_for_each_result_category(): void
    {
        [$user, $journey] = $this->completedAnswersFixture();
        $journey->higherEducationCourses()->attach($this->educationCourse(8.829)->PK_CURSO_SUPERIOR);
        $journey->professionalCertifications()->attach(
            $this->certification('Certificacao Teste')->PK_CERTIFICACAO_PROFISSIONAL,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $detail = $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.score_breakdown.0.score', 20)
            ->assertJsonPath('data.score_breakdown.1.score', 50)
            ->assertJsonPath('data.score_breakdown.2.score', 41.131)
            ->assertJsonPath('data.score_breakdown.3.score', 8.829)
            ->assertJsonPath('data.score_breakdown.4.score', 20)
            ->assertJsonPath('data.score_breakdown.5.score', 10);

        $storedBreakdown = $journey->fresh()->JS_SCORE_BREAKDOWN;
        $this->assertEquals($storedBreakdown, $detail->json('data.score_breakdown'));

        Criterion::query()
            ->where('NO_CRITERIO_EN', Criterion::PROFESSIONAL_EXPERIENCE_NAME)
            ->update(['NU_PESO_CRITERIO' => 99]);
        Subcriterion::query()
            ->where('PK_SUBCRITERIOS', $journey->FK_SUBCRITERIOS_INGLES)
            ->update(['NU_PESO_SUBCRITERIO' => 99]);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.score_breakdown', $storedBreakdown);
    }

    public function test_it_finalizes_a_journey_answered_only_with_competencies(): void
    {
        Carbon::setTestNow('2026-09-28 14:30:00');
        [$user, $journey] = $this->completedAnswersFixture(withPreQuestionnaireAnswers: false);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk()
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
            'FK_SUBCRITERIOS_INGLES' => null,
            'NU_PONTUACAO_TOTAL' => 100,
            'DH_FIM' => '2026-09-28 14:30:00',
        ]);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.score', 100);
    }

    public function test_it_scores_zero_when_no_competency_is_declared(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(
            withPreQuestionnaireAnswers: false,
            includeSecondCompetency: true,
            firstAcquired: false,
            secondAcquired: false,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 0,
        ]);
    }

    public function test_it_scores_the_declared_competencies_over_the_profile_total(): void
    {
        // Governanca (peso 25, Basico = 2) vale 10; Risco (peso 15, Avancado = 4) vale 12
        [$user, $journey] = $this->completedAnswersFixture(
            withPreQuestionnaireAnswers: false,
            includeSecondCompetency: true,
            secondAcquired: false,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        // 10 / (10 + 12) × 100
        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 45.455,
        ]);
    }

    public function test_it_scores_one_hundred_when_every_competency_is_declared(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(
            withPreQuestionnaireAnswers: false,
            includeSecondCompetency: true,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 100,
        ]);
    }

    public function test_it_ignores_legacy_answers_and_criterion_weights_in_the_total_score(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(
            experienceWeight: 99.0,
            competencyWeight: 10.0,
            includeSecondCompetency: true,
            secondAcquired: false,
        );
        $journey->higherEducationCourses()->attach($this->educationCourse(15.751)->PK_CURSO_SUPERIOR);
        $journey->professionalCertifications()->attach(
            $this->certification('Certificacao Teste')->PK_CERTIFICACAO_PROFISSIONAL,
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 45.455,
        ]);
    }

    public function test_it_rejects_an_incomplete_journey_without_changing_it(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-09-28 10:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Existem etapas obrigatorias pendentes.')
            ->assertJsonPath('code', 'INCOMPLETE_JOURNEY')
            // O pré-questionário saiu do fluxo: só as competências são exigidas
            ->assertJsonPath('errors.fields', [
                'answers.competencies',
            ]);

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => null,
            'DH_FIM' => null,
        ]);
    }

    public function test_it_returns_conflict_when_the_latest_journey_is_already_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey(
            $user,
            $profile,
            '2026-09-28 10:00:00',
            '2026-09-28 11:00:00',
        );

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertConflict()
            ->assertJsonPath('code', 'JOURNEY_ALREADY_FINISHED');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => null,
            'DH_FIM' => '2026-09-28 11:00:00',
        ]);
    }

    public function test_it_returns_not_found_when_the_user_has_no_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_requires_authentication(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_requires_a_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)
            ->postJson('/api/journeys/current/finalize')
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }

    private function completedAnswersFixture(
        ?float $experienceWeight = 23.81,
        bool $createExperienceCriterion = true,
        float $competencyWeight = 45.92,
        bool $includeCompetencyCriterion = true,
        bool $includeSecondCompetency = false,
        bool $withPreQuestionnaireAnswers = true,
        bool $firstAcquired = true,
        bool $secondAcquired = true,
    ): array {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $professionalExperience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Menos de 1 ano',
        ]);
        $profileExperience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Entre 7 e 10 anos',
        ]);
        $englishCriterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Nível de Proficiência - Inglês',
            'NU_PESO_CRITERIO' => 4.85,
        ]);
        $experienceCriterion = Criterion::query()
            ->where('NO_CRITERIO_PT', 'Experiência Profissional')
            ->orWhere('NO_CRITERIO_EN', 'Professional Experience')
            ->first();

        if ($createExperienceCriterion) {
            $experienceCriterion ??= new Criterion;
            $experienceCriterion->fill([
                'NO_CRITERIO_PT' => 'Experiência Profissional',
                'NO_CRITERIO_EN' => 'Professional Experience',
                'NU_PESO_CRITERIO' => $experienceWeight,
            ])->save();
        } else {
            $experienceCriterion?->update(['NU_PESO_CRITERIO' => null]);
        }
        $english = Subcriterion::create([
            'FK_CRITERIOS' => $englishCriterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Intermediário - Leitura e Escrita',
            'NU_PESO_SUBCRITERIO' => 26.0505,
        ]);
        Criterion::create([
            'NO_CRITERIO_PT' => 'Certificação Profissional',
            'NO_CRITERIO_EN' => Criterion::PROFESSIONAL_CERTIFICATION_NAME,
            'NU_PESO_CRITERIO' => 10.72,
        ]);
        $competencyCriterion = Criterion::query()
            ->where('NO_CRITERIO_PT', Criterion::COMPETENCIES_NAME_PT)
            ->orWhere('NO_CRITERIO_EN', Criterion::COMPETENCIES_NAME_EN)
            ->first();
        if ($includeCompetencyCriterion) {
            $competencyCriterion ??= new Criterion;
            $competencyCriterion->fill([
                'NO_CRITERIO_PT' => Criterion::COMPETENCIES_NAME_PT,
                'NO_CRITERIO_EN' => Criterion::COMPETENCIES_NAME_EN,
                'NU_PESO_CRITERIO' => $competencyWeight,
            ])->save();
        } elseif ($competencyCriterion !== null) {
            $competencyCriterion->delete();
        }
        $area = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Governanca']);
        $difficulty = Difficulty::create(['NO_DIFICULDADE_PT' => 'Basico']);
        $competency = Competency::create([
            'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_COMPETENCIAS_PT' => 'LGPD',
        ]);
        ProfileCompetency::create([
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
        ]);
        CompetencyCriterionWeight::create([
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
            'NU_PESO_CRIT_COMPETENCIA' => 25,
        ]);
        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $withPreQuestionnaireAnswers ? $professionalExperience->PK_EXPERIENCIA_ATUACAO : null,
            'FK_ATUACAO_PERFIL' => $withPreQuestionnaireAnswers ? $profileExperience->PK_EXPERIENCIA_ATUACAO : null,
            'FK_SUBCRITERIOS_INGLES' => $withPreQuestionnaireAnswers ? $english->PK_SUBCRITERIOS : null,
            'DH_CRIADO_EM' => '2026-09-28 10:00:00',
        ]);
        AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => $firstAcquired ? 1 : 0,
        ]);

        if ($includeSecondCompetency) {
            $secondArea = KnowledgeArea::create(['NO_AREA_CONHECIMENTO_PT' => 'Risco']);
            $advanced = Difficulty::create(['NO_DIFICULDADE_PT' => 'Avancado']);
            $secondCompetency = Competency::create([
                'FK_AREA_CONHECIMENTO' => $secondArea->PK_AREA_CONHECIMENTO,
                'FK_DIFICULDADE' => $advanced->PK_DIFICULDADE,
                'NO_COMPETENCIAS_PT' => 'Gestao de riscos',
            ]);
            ProfileCompetency::create([
                'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
                'FK_COMPETENCIAS' => $secondCompetency->PK_COMPETENCIAS,
            ]);
            CompetencyCriterionWeight::create([
                'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
                'FK_AREA_CONHECIMENTO' => $secondArea->PK_AREA_CONHECIMENTO,
                'NU_PESO_CRIT_COMPETENCIA' => 15,
            ]);
            AcquiredKnowledge::create([
                'FK_JORNADA' => $journey->PK_JORNADA,
                'FK_COMPETENCIAS' => $secondCompetency->PK_COMPETENCIAS,
                'TP_ADQUIRIDO' => $secondAcquired ? 1 : 0,
            ]);
        }

        return [$user, $journey];
    }

    private function educationCourse(float $subcriterionWeight, string $courseName = 'Curso Teste'): HigherEducationCourse
    {
        $criterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Formação Acadêmica',
            'NO_CRITERIO_EN' => 'Education',
            'NU_PESO_CRITERIO' => 14.7,
        ]);
        $subcriterion = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => $courseName,
            'NU_PESO_SUBCRITERIO' => $subcriterionWeight,
        ]);

        return HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $subcriterion->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => $courseName,
        ]);
    }
}
