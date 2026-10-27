<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\Journey;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JourneyCalculationService
{
    private const EXPERIENCE_SCORES = [
        'menos de 1 ano' => 10.0,
        'less than 1 year' => 10.0,
        'entre 1 e 3 anos' => 12.5,
        'between 1 and 3 years' => 12.5,
        'entre 4 e 6 anos' => 16.67,
        'between 4 and 6 years' => 16.67,
        'entre 7 e 10 anos' => 25.0,
        'between 7 and 10 years' => 25.0,
        'mais de 10 anos' => 50.0,
        'more than 10 years' => 50.0,
    ];

    private const NONE_EXPERIENCE_LABELS = [
        'nenhum',
        'none',
    ];

    private const COMPETENCY_LEVEL_WEIGHTS = [
        'iniciante' => 1.0,
        'beginner' => 1.0,
        'basico' => 2.0,
        'basic' => 2.0,
        'intermediario' => 3.0,
        'intermediary' => 3.0,
        'avancado' => 4.0,
        'advanced' => 4.0,
        'especialista' => 5.0,
        'specialist' => 5.0,
    ];

    public function __construct(
        private readonly CompetencyCriterionWeightRepositoryInterface $competencyCriterionWeightRepository,
        private readonly CriterionRepositoryInterface $criterionRepository,
    ) {}

    public function calculateForJourney(Journey $journey): float
    {
        $areaWeights = $journey->FK_PERFIS_JORNADA === null
            ? collect()
            : $this->competencyCriterionWeightRepository->findByJourneyProfileId((int) $journey->FK_PERFIS_JORNADA);

        return $this->calculateProfessionalExperienceCriterionScore(
            $journey->professionalExperience,
            $journey->profileExperience,
        )
            + $this->calculateEnglishProficiencyScore($journey->englishSubcriterion)
            + $this->calculateEducationCriterionScore($journey->higherEducationCourses)
            + $this->calculateProfessionalCertificationCriterionScore($journey->professionalCertifications)
            + $this->calculateCompetencyCriterionScore($journey->acquiredKnowledges, $areaWeights);
    }

    public function calculateProfessionalExperienceScore(ProfessionalExperience|string|null $experience): float
    {
        return $this->calculateExperienceScoreAllowingNone($experience);
    }

    public function calculateProfileExperienceScore(ProfessionalExperience|string|null $experience): float
    {
        return $this->calculateExperienceScoreRejectingNone($experience);
    }

    public function calculateProfessionalExperienceCriterionScore(
        ProfessionalExperience|string|null $professionalExperience,
        ProfessionalExperience|string|null $profileExperience,
    ): float {
        $criterionScore = $this->calculateProfessionalExperienceScore($professionalExperience)
            + $this->calculateProfileExperienceScore($profileExperience);

        $criterionWeight = $this->criterionRepository->findWeightByName(Criterion::PROFESSIONAL_EXPERIENCE_NAME);

        if ($criterionWeight === null) {
            Log::warning('Professional experience criterion weight is not configured; using a zero contribution.', [
                'criterion' => Criterion::PROFESSIONAL_EXPERIENCE_NAME,
            ]);

            return 0.0;
        }

        return $criterionScore * $criterionWeight / 100.0;
    }

    public function calculateEnglishProficiencyScore(?Subcriterion $subcriterion): float
    {
        if ($subcriterion === null) {
            return 0.0;
        }

        // NU_PESO_CRITERIO é nullable no schema legado. Sem critério ou peso,
        // a contribuição é zero, mas o problema de configuração deve ficar visível.
        $criterion = $subcriterion->criterion;
        $mainCriterionWeight = $criterion?->NU_PESO_CRITERIO;

        if ($mainCriterionWeight === null) {
            Log::warning('Peso do critério de Inglês não configurado.', [
                'criterion' => Criterion::ENGLISH_PROFICIENCY_NAME,
            ]);
        }

        return (float) ($subcriterion->NU_PESO_SUBCRITERIO ?? 0) * ((float) ($mainCriterionWeight ?? 0) / 100.0);
    }

    private function calculateExperienceScoreAllowingNone(ProfessionalExperience|string|null $experience): float
    {
        if ($experience === null) {
            return 0.0;
        }

        if ($this->hasNoneExperienceLabel($experience)) {
            return 0.0;
        }

        return $this->experienceScore($experience);
    }

    private function calculateExperienceScoreRejectingNone(ProfessionalExperience|string|null $experience): float
    {
        if ($experience === null) {
            return 0.0;
        }

        if ($this->hasNoneExperienceLabel($experience)) {
            throw new InvalidArgumentException('Experiencia "Nenhum" nao permitida para este calculo.');
        }

        return $this->experienceScore($experience);
    }

    public function calculateCourseScore(iterable $courses): float
    {
        return collect($courses)
            ->mapWithKeys(function ($course): array {
                $subcriterion = $course->subcriterion ?? null;

                if ($subcriterion === null) {
                    return [];
                }

                $key = $subcriterion->PK_SUBCRITERIOS
                    ?? $this->normalizeLabel($subcriterion->NO_SUBCRITERIO_PT ?? $subcriterion->NO_SUBCRITERIO_EN ?? null);

                if ($key === '') {
                    return [];
                }

                return [(string) $key => (float) ($subcriterion->NU_PESO_SUBCRITERIO ?? 0)];
            })
            ->sum();
    }

    public function calculateEducationCriterionScore(iterable $courses): float
    {
        $courses = collect($courses);
        // Todos os tipos de curso selecionados pertencem ao criterio de Formacao Academica.
        $criterionWeight = $courses
            ->map(fn ($course): mixed => $course->subcriterion?->criterion?->NU_PESO_CRITERIO)
            ->first(fn ($weight): bool => $weight !== null);

        if ($criterionWeight === null) {
            if ($courses->isNotEmpty()) {
                Log::warning('Education criterion weight is not configured; using a zero contribution.', [
                    'criterion' => Criterion::EDUCATION_NAME,
                ]);
            }

            return 0.0;
        }

        return $this->calculateCourseScore($courses) * (float) $criterionWeight / 100.0;
    }

    public function calculateCertificationScore(iterable|int $certifications): float
    {
        $quantity = is_int($certifications)
            ? $certifications
            : collect($certifications)->count();

        return min(max($quantity, 0) * 100.0 / 5.0, 100.0);
    }

    public function calculateProfessionalCertificationCriterionScore(iterable|int $certifications): float
    {
        $weight = $this->criterionRepository->findWeightByName(Criterion::PROFESSIONAL_CERTIFICATION_NAME);

        if ($weight === null) {
            Log::warning('Professional certification criterion weight is not configured; using a zero contribution.', [
                'criterion' => Criterion::PROFESSIONAL_CERTIFICATION_NAME,
            ]);

            return 0.0;
        }

        return $this->calculateCertificationScore($certifications)
            * $weight
            / 100.0;
    }

    public function calculateCompetencyScore(iterable $acquiredKnowledges, iterable $areaWeights): float
    {
        $weightsByArea = $this->areaWeightsByAreaId($areaWeights);

        return collect($acquiredKnowledges)
            ->filter(fn ($acquiredKnowledge): bool => (int) $acquiredKnowledge->TP_ADQUIRIDO === 1)
            ->sum(function ($acquiredKnowledge) use ($weightsByArea): float {
                $competency = $acquiredKnowledge->competency ?? null;

                if ($competency === null || $competency->FK_AREA_CONHECIMENTO === null) {
                    return 0.0;
                }

                $areaWeight = $weightsByArea->get((int) $competency->FK_AREA_CONHECIMENTO, 0.0);
                $levelWeight = $this->competencyLevelWeight($competency->difficulty ?? null);

                return $areaWeight * ($levelWeight / 5.0);
            });
    }

    public function calculateCompetencyCriterionScore(iterable $acquiredKnowledges, iterable $areaWeights): float
    {
        $criterionScore = $this->calculateCompetencyScore($acquiredKnowledges, $areaWeights);
        $criterionWeight = $this->criterionRepository->findWeightByName(Criterion::COMPETENCIES_NAME_EN);

        if ($criterionWeight === null) {
            if ($criterionScore > 0.0) {
                Log::warning('Competency criterion weight is not configured; using a zero contribution.', [
                    'criterion' => Criterion::COMPETENCIES_NAME_EN,
                ]);
            }

            return 0.0;
        }

        return $criterionScore * ($criterionWeight / 100.0);
    }

    private function areaWeightsByAreaId(iterable $areaWeights): Collection
    {
        return collect($areaWeights)
            ->filter(fn ($areaWeight): bool => $areaWeight->FK_AREA_CONHECIMENTO !== null)
            ->groupBy(fn ($areaWeight): int => (int) $areaWeight->FK_AREA_CONHECIMENTO)
            ->map(fn (Collection $weights): float => $weights->sum(
                fn ($weight): float => (float) ($weight->NU_PESO_CRIT_COMPETENCIA ?? 0),
            ));
    }

    private function competencyLevelWeight(mixed $difficulty): float
    {
        if ($difficulty === null) {
            return 0.0;
        }

        $labels = [
            $difficulty->NO_DIFICULDADE_PT ?? null,
            $difficulty->NO_DIFICULDADE_EN ?? null,
        ];

        foreach ($labels as $label) {
            $normalizedLabel = $this->normalizeLabel($label);

            if (array_key_exists($normalizedLabel, self::COMPETENCY_LEVEL_WEIGHTS)) {
                return self::COMPETENCY_LEVEL_WEIGHTS[$normalizedLabel];
            }
        }

        return 0.0;
    }

    private function hasNoneExperienceLabel(ProfessionalExperience|string $experience): bool
    {
        foreach ($this->experienceLabels($experience) as $label) {
            if (in_array($this->normalizeLabel($label), self::NONE_EXPERIENCE_LABELS, true)) {
                return true;
            }
        }

        return false;
    }

    private function experienceScore(ProfessionalExperience|string $experience): float
    {
        foreach ($this->experienceLabels($experience) as $label) {
            $normalizedLabel = $this->normalizeLabel($label);

            if (array_key_exists($normalizedLabel, self::EXPERIENCE_SCORES)) {
                return self::EXPERIENCE_SCORES[$normalizedLabel];
            }
        }

        return 0.0;
    }

    private function experienceLabels(ProfessionalExperience|string $experience): array
    {
        return is_string($experience)
            ? [$experience]
            : [$experience->DS_EXPERIENCIA_ATUACAO_PT, $experience->DS_EXPERIENCIA_ATUACAO_EN];
    }

    private function normalizeLabel(?string $label): string
    {
        return Str::lower(Str::ascii(trim((string) $label)));
    }
}
