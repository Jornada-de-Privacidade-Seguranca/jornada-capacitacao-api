<?php

namespace Tests\Unit;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\CompetencyCriterionWeight;
use App\Models\Difficulty;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use App\Services\JourneyCalculationService;
use Illuminate\Database\Eloquent\Collection;
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

    public function test_it_calculates_certification_score_with_maximum_limit(): void
    {
        $service = $this->service();

        $this->assertSame(0.0, $service->calculateCertificationScore(0));
        $this->assertSame(20.0, $service->calculateCertificationScore(1));
        $this->assertSame(60.0, $service->calculateCertificationScore(3));
        $this->assertSame(100.0, $service->calculateCertificationScore(5));
        $this->assertSame(100.0, $service->calculateCertificationScore(8));
    }

    public function test_it_calculates_competency_score_with_hardcoded_level_weights_and_area_weights(): void
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
            (new AcquiredKnowledge)->setRelation('competency', $advancedCompetency),
            (new AcquiredKnowledge)->setRelation('competency', $specialistCompetency),
            (new AcquiredKnowledge)->setRelation('competency', $missingWeightCompetency),
        ], $areaWeights));
    }

    public function test_it_calculates_journey_total_using_profile_area_weights_from_repository(): void
    {
        $areaWeight = new CompetencyCriterionWeight([
            'FK_PERFIS_JORNADA' => 77,
            'FK_AREA_CONHECIMENTO' => 10,
            'NU_PESO_CRIT_COMPETENCIA' => 25,
        ]);
        $service = $this->service(new Collection([$areaWeight]));

        $graduation = new Subcriterion(['NU_PESO_SUBCRITERIO' => 8.829]);
        $graduation->PK_SUBCRITERIOS = 1;

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
        $journey->setRelation('higherEducationCourses', new Collection([$course]));
        $journey->setRelation('professionalCertifications', new Collection([
            new ProfessionalCertification,
            new ProfessionalCertification,
        ]));
        $journey->setRelation('acquiredKnowledges', new Collection([
            (new AcquiredKnowledge)->setRelation('competency', $competency),
        ]));

        $this->assertEqualsWithDelta(93.829, $service->calculateForJourney($journey), 0.0001);
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
