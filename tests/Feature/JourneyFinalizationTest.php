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

    public function test_it_finalizes_the_current_journey_and_persists_its_score(): void
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

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.score', 65.642);

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 65.642,
            'DH_FIM' => '2026-09-28 14:30:00',
        ]);
        $this->assertDatabaseCount('RL_CURSOS_SUPERIORES_JORNADA', 0);
        $this->assertDatabaseCount('RL_CERTIFICACAO_JORNADA', 0);
    }

    public function test_it_includes_the_weighted_contribution_of_one_selected_education_course(): void
    {
        [$user, $journey] = $this->completedAnswersFixture();
        $journey->higherEducationCourses()->attach($this->educationCourse(8.829)->PK_CURSO_SUPERIOR);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 20.895,
        ]);
    }

    public function test_it_sums_the_weighted_contributions_of_multiple_selected_education_types(): void
    {
        [$user, $journey] = $this->completedAnswersFixture();
        $graduation = $this->educationCourse(8.829, 'Graduacao');
        $postgraduate = $this->educationCourse(15.751, 'Pos-graduacao');
        $journey->higherEducationCourses()->attach([
            $graduation->PK_CURSO_SUPERIOR,
            $postgraduate->PK_CURSO_SUPERIOR,
        ]);
        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 23.21,
        ]);
    }

    public function test_it_uses_the_configured_professional_experience_criterion_weight(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(30.0);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 21.763,
        ]);
    }

    public function test_it_uses_zero_when_professional_experience_criterion_weight_is_missing(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(null, false);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 11.263,
        ]);
    }

    public function test_it_applies_the_weighted_contribution_of_one_certification(): void
    {
        [$user, $journey] = $this->completedAnswersFixture();
        $journey->professionalCertifications()->attach($this->certification('Certificacao 1')->PK_CERTIFICACAO_PROFISSIONAL);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 21.741,
        ]);
    }

    public function test_it_caps_the_weighted_contribution_at_five_certifications(): void
    {
        [$user, $journey] = $this->completedAnswersFixture();
        $certificationIds = [];

        for ($count = 1; $count <= 6; $count++) {
            $certificationIds[] = $this->certification('Certificacao '.$count)->PK_CERTIFICACAO_PROFISSIONAL;
        }

        $journey->professionalCertifications()->attach($certificationIds);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 30.317,
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
            ->assertJsonPath('errors.fields', [
                'answers.professional_experience',
                'answers.profile_experience',
                'answers.english_proficiency',
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

    public function test_it_applies_the_competency_weight_to_multiple_acquired_competencies(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(includeSecondCompetency: true);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 71.152,
        ]);
    }

    public function test_it_uses_the_competency_criterion_weight_from_the_database(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(competencyWeight: 50.0);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 66.05,
        ]);
    }

    public function test_it_counts_a_missing_competency_criterion_weight_as_zero(): void
    {
        [$user, $journey] = $this->completedAnswersFixture(includeCompetencyCriterion: false);

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/current/finalize')
            ->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'NU_PONTUACAO_TOTAL' => 61.05,
        ]);
    }

    private function completedAnswersFixture(
        ?float $experienceWeight = 23.81,
        bool $createExperienceCriterion = true,
        float $competencyWeight = 45.92,
        bool $includeCompetencyCriterion = true,
        bool $includeSecondCompetency = false,
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
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_SUBCRITERIOS_INGLES' => $english->PK_SUBCRITERIOS,
            'DH_CRIADO_EM' => '2026-09-28 10:00:00',
        ]);
        AcquiredKnowledge::create([
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            'TP_ADQUIRIDO' => 1,
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
                'TP_ADQUIRIDO' => 1,
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
