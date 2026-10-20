<?php

namespace Tests\Unit;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\CompetencyCriterionWeight;
use App\Models\Criterion;
use App\Models\Difficulty;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use App\Services\JourneyCalculationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Tests\TestCase;

class JourneyCalculationServiceTest extends TestCase
{
    public function test_it_calculates_professional_experience_ranges(): void
    {
        $service = $this->service();

        $this->assertSame(10.0, $service->calculateProfessionalExperienceScore('Menos de 1 ano'));
        $this->assertSame(12.5, $service->calculateProfessionalExperienceScore('Entre 1 e 3 anos'));
        $this->assertSame(16.67, $service->calculateProfessionalExperienceScore('Entre 4 e 6 anos'));
        $this->assertSame(25.0, $service->calculateProfessionalExperienceScore('Entre 7 e 10 anos'));
        $this->assertSame(50.0, $service->calculateProfessionalExperienceScore('Mais de 10 anos'));
    }

    public function test_it_allows_none_only_when_calculating_professional_experience_that_accepts_none(): void
    {
        $service = $this->service();

        $this->assertSame(0.0, $service->calculateProfessionalExperienceScore('Nenhum'));

        $this->expectException(InvalidArgumentException::class);

        $service->calculateProfileExperienceScore('Nenhum');
    }

    public function test_it_applies_the_professional_experience_criterion_weight_to_both_experience_answers(): void
    {
        $service = $this->service();

        $this->assertEqualsWithDelta(
            2.381,
            $service->calculateProfessionalExperienceCriterionScore('Nenhum', 'Menos de 1 ano'),
            0.0001,
        );
        $this->assertEqualsWithDelta(
            23.81,
            $service->calculateProfessionalExperienceCriterionScore('Mais de 10 anos', 'Mais de 10 anos'),
            0.0001,
        );
        $this->assertEqualsWithDelta(
            6.945377,
            $service->calculateProfessionalExperienceCriterionScore('Entre 1 e 3 anos', 'Entre 4 e 6 anos'),
            0.0001,
        );
    }

    public function test_it_counts_one_course_per_subcriterion_without_affinity(): void
    {
        $service = $this->service();
        $graduation = new Subcriterion([
            'NU_PESO_SUBCRITERIO' => 8.829,
        ]);
        $graduation->PK_SUBCRITERIOS = 1;

        $postgraduate = new Subcriterion([
            'NU_PESO_SUBCRITERIO' => 15.751,
        ]);
        $postgraduate->PK_SUBCRITERIOS = 2;

        $firstGraduation = (new HigherEducationCourse)->setRelation('subcriterion', $graduation);
        $secondGraduation = (new HigherEducationCourse)->setRelation('subcriterion', $graduation);
        $postgraduateCourse = (new HigherEducationCourse)->setRelation('subcriterion', $postgraduate);

        $this->assertEqualsWithDelta(
            24.58,
            $service->calculateCourseScore([$firstGraduation, $secondGraduation, $postgraduateCourse]),
            0.0001,
        );
    }

    public function test_it_applies_the_english_main_criterion_weight_to_subcriterion_score(): void
    {
        $service = $this->service();
        $criterion = new Criterion(['NU_PESO_CRITERIO' => 4.85]);
        $none = (new Subcriterion(['NU_PESO_SUBCRITERIO' => 0]))->setRelation('criterion', $criterion);
        $intermediate = (new Subcriterion(['NU_PESO_SUBCRITERIO' => 26.05]))->setRelation('criterion', $criterion);
        $advanced = (new Subcriterion(['NU_PESO_SUBCRITERIO' => 63.335]))->setRelation('criterion', $criterion);

        $this->assertSame(0.0, $service->calculateEnglishProficiencyScore($none));
        $this->assertEqualsWithDelta(1.263425, $service->calculateEnglishProficiencyScore($intermediate), 0.000001);
        $this->assertEqualsWithDelta(3.0717475, $service->calculateEnglishProficiencyScore($advanced), 0.000001);
        $this->assertSame(0.0, $service->calculateEnglishProficiencyScore(null));
    }

    public function test_it_applies_the_configured_education_weight_to_selected_course_types(): void
    {
        $service = $this->service();
        $criterion = new Criterion(['NU_PESO_CRITERIO' => 14.7]);
        $graduation = $this->courseWithEducationWeight(8.829, 1, $criterion);
        $postgraduate = $this->courseWithEducationWeight(15.751, 2, $criterion);

        $this->assertSame(0.0, $service->calculateEducationCriterionScore([]));
        $this->assertEqualsWithDelta(
            1.297863,
            $service->calculateEducationCriterionScore([$graduation]),
            0.0001,
        );
        $this->assertEqualsWithDelta(
            3.61326,
            $service->calculateEducationCriterionScore([$graduation, $postgraduate]),
            0.0001,
        );
        $this->assertEqualsWithDelta(
            3.61326,
            $service->calculateEducationCriterionScore([$graduation, $graduation, $postgraduate]),
            0.0001,
        );
    }

    public function test_it_treats_a_null_education_criterion_weight_as_zero(): void
    {
        $service = $this->service();
        $criterion = new Criterion(['NU_PESO_CRITERIO' => null]);
        $courseWithNullWeight = $this->courseWithEducationWeight(8.829, 1, $criterion);
        $courseWithoutCriterion = (new HigherEducationCourse)->setRelation(
            'subcriterion',
            new Subcriterion(['NU_PESO_SUBCRITERIO' => 8.829]),
        );

        Log::shouldReceive('warning')
            ->twice()
            ->with('Education criterion weight is not configured; using a zero contribution.', [
                'criterion' => Criterion::EDUCATION_NAME,
            ]);

        $this->assertSame(0.0, $service->calculateEducationCriterionScore([$courseWithNullWeight]));
        $this->assertSame(0.0, $service->calculateEducationCriterionScore([$courseWithoutCriterion]));
    }

    public function test_it_treats_a_missing_english_criterion_or_weight_as_zero(): void
    {
        Log::shouldReceive('warning')
            ->twice()
            ->with('Peso do critério de Inglês não configurado.', [
                'criterion' => Criterion::ENGLISH_PROFICIENCY_NAME,
            ]);

        $service = $this->service();
        $weightedSubcriterion = new Subcriterion(['NU_PESO_SUBCRITERIO' => 63.335]);
        $criterionWithoutWeight = new Criterion(['NU_PESO_CRITERIO' => null]);
        $weightedSubcriterion->setRelation('criterion', $criterionWithoutWeight);

        $this->assertSame(0.0, $service->calculateEnglishProficiencyScore($weightedSubcriterion));
        $this->assertSame(
            0.0,
            $service->calculateEnglishProficiencyScore(new Subcriterion(['NU_PESO_SUBCRITERIO' => 63.335])),
        );
    }

    public function test_it_calculates_certification_score_with_maximum_limit(): void
    {
        $service = $this->service();

        $this->assertSame(0.0, $service->calculateCertificationScore(0));
        $this->assertSame(20.0, $service->calculateCertificationScore(1));
        $this->assertSame(60.0, $service->calculateCertificationScore(3));
        $this->assertSame(100.0, $service->calculateCertificationScore(5));
        $this->assertSame(100.0, $service->calculateCertificationScore(8));
    }

    public function test_it_calculates_only_acquired_competencies_with_area_weights(): void
    {
        $service = $this->service();
        $advanced = new Difficulty([
            'NO_DIFICULDADE_PT' => 'Avançado',
            'NO_DIFICULDADE_EN' => 'Advanced',
        ]);
        $specialist = new Difficulty([
            'NO_DIFICULDADE_PT' => 'Especialista',
            'NO_DIFICULDADE_EN' => 'Specialist',
        ]);

        $advancedCompetency = new Competency(['FK_AREA_CONHECIMENTO' => 10]);
        $advancedCompetency->setRelation('difficulty', $advanced);

        $specialistCompetency = new Competency(['FK_AREA_CONHECIMENTO' => 20]);
        $specialistCompetency->setRelation('difficulty', $specialist);

        $missingWeightCompetency = new Competency(['FK_AREA_CONHECIMENTO' => 30]);
        $missingWeightCompetency->setRelation('difficulty', $specialist);

        $areaWeights = [
            new CompetencyCriterionWeight([
                'FK_AREA_CONHECIMENTO' => 10,
                'NU_PESO_CRIT_COMPETENCIA' => 50,
            ]),
            new CompetencyCriterionWeight([
                'FK_AREA_CONHECIMENTO' => 20,
                'NU_PESO_CRIT_COMPETENCIA' => 25,
            ]),
        ];

        $this->assertSame(65.0, $service->calculateCompetencyScore([
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 1]))->setRelation('competency', $advancedCompetency),
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 1]))->setRelation('competency', $specialistCompetency),
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 1]))->setRelation('competency', $missingWeightCompetency),
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 0]))->setRelation('competency', $advancedCompetency),
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => null]))->setRelation('competency', $specialistCompetency),
        ], $areaWeights));
    }

    public function test_it_sums_multiple_weights_for_the_same_knowledge_area(): void
    {
        $service = $this->service();
        $difficulty = new Difficulty(['NO_DIFICULDADE_PT' => 'Avançado']);
        $competency = new Competency(['FK_AREA_CONHECIMENTO' => 10]);
        $competency->setRelation('difficulty', $difficulty);

        $this->assertSame(32.0, $service->calculateCompetencyScore([
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 1]))->setRelation('competency', $competency),
        ], [
            new CompetencyCriterionWeight([
                'FK_AREA_CONHECIMENTO' => 10,
                'NU_PESO_CRIT_COMPETENCIA' => 25,
            ]),
            new CompetencyCriterionWeight([
                'FK_AREA_CONHECIMENTO' => 10,
                'NU_PESO_CRIT_COMPETENCIA' => 15,
            ]),
        ]));
    }

    public function test_it_calculates_journey_total_using_profile_area_weights_from_repository(): void
    {
        $areaWeight = new CompetencyCriterionWeight([
            'FK_PERFIS_JORNADA' => 77,
            'FK_AREA_CONHECIMENTO' => 10,
            'NU_PESO_CRIT_COMPETENCIA' => 25,
        ]);
        $service = $this->service(new Collection([$areaWeight]));

        $graduation = $this->courseWithEducationWeight(
            8.829,
            1,
            new Criterion(['NU_PESO_CRITERIO' => 14.7]),
        )->subcriterion;

        $course = (new HigherEducationCourse)->setRelation('subcriterion', $graduation);
        $difficulty = new Difficulty(['NO_DIFICULDADE_PT' => 'Básico']);
        $competency = new Competency(['FK_AREA_CONHECIMENTO' => 10]);
        $competency->setRelation('difficulty', $difficulty);

        $journey = new Journey([
            'FK_PERFIS_JORNADA' => 77,
        ]);
        $journey->setRelation('professionalExperience', new ProfessionalExperience([
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Menos de 1 ano',
        ]));
        $journey->setRelation('profileExperience', new ProfessionalExperience([
            'DS_EXPERIENCIA_ATUACAO_PT' => 'Entre 7 e 10 anos',
        ]));
        $englishCriterion = new Criterion(['NU_PESO_CRITERIO' => 4.85]);
        $journey->setRelation('englishSubcriterion', (new Subcriterion([
            'NU_PESO_SUBCRITERIO' => 26.05,
        ]))->setRelation('criterion', $englishCriterion));
        $journey->setRelation('higherEducationCourses', new Collection([$course]));
        $journey->setRelation('professionalCertifications', new Collection([
            new ProfessionalCertification,
            new ProfessionalCertification,
        ]));
        $journey->setRelation('acquiredKnowledges', new Collection([
            (new AcquiredKnowledge(['TP_ADQUIRIDO' => 1]))->setRelation('competency', $competency),
        ]));

        $this->assertEqualsWithDelta(60.894788, $service->calculateForJourney($journey), 0.0001);
    }

    private function courseWithEducationWeight(float $weight, int $subcriterionId, Criterion $criterion): HigherEducationCourse
    {
        $subcriterion = new Subcriterion(['NU_PESO_SUBCRITERIO' => $weight]);
        $subcriterion->PK_SUBCRITERIOS = $subcriterionId;
        $subcriterion->setRelation('criterion', $criterion);

        return (new HigherEducationCourse)->setRelation('subcriterion', $subcriterion);
    }

    private function service(?Collection $areaWeights = null): JourneyCalculationService
    {
        $repository = new class($areaWeights ?? new Collection) implements CompetencyCriterionWeightRepositoryInterface
        {
            public function __construct(private readonly Collection $areaWeights) {}

            public function all(): Collection
            {
                return $this->areaWeights;
            }

            public function findByJourneyProfileId(int $journeyProfileId): Collection
            {
                return $this->areaWeights
                    ->where('FK_PERFIS_JORNADA', $journeyProfileId)
                    ->values();
            }

            public function findOrFail(int $id): CompetencyCriterionWeight
            {
                return $this->areaWeights->firstOrFail('PK_PESO_CRIT_COMPETENCIA', $id);
            }
        };

        return new JourneyCalculationService($repository);
    }
}
