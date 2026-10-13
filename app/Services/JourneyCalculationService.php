<?php

namespace App\Services;

use App\Models\Journey;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Repositories\Contracts\CompetencyCriterionWeightRepositoryInterface;
use Illuminate\Support\Collection;
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

    private const NONE_EXPERIENCE_LABELS = ['nenhum', 'none'];

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
    ) {}

    public function calculateForJourney(Journey $journey): float
    {
        $areaWeights = $journey->FK_PERFIS_JORNADA === null
            ? collect()
            : $this->competencyCriterionWeightRepository->findByJourneyProfileId((int) $journey->FK_PERFIS_JORNADA);

        return $this->calculateProfessionalExperienceScore($journey->professionalExperience)
            + $this->calculateProfileExperienceScore($journey->profileExperience)
            + $this->calculateEnglishProficiencyScore($journey->englishSubcriterion)
            + $this->calculateCourseScore($journey->higherEducationCourses)
            + $this->calculateCertificationScore($journey->professionalCertifications)
            + $this->calculateCompetencyScore($journey->acquiredKnowledges, $areaWeights);
    }

    public function calculateProfessionalExperienceScore(ProfessionalExperience|string|null $experience): float
    {
        return $this->calculateExperienceScore($experience, true);
    }

    public function calculateProfileExperienceScore(ProfessionalExperience|string|null $experience): float
    {
        return $this->calculateExperienceScore($experience, false);
    }

    public function calculateEnglishProficiencyScore(?Subcriterion $subcriterion): float
    {
        return (float) ($subcriterion?->NU_PESO_SUBCRITERIO ?? 0);
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

                return $key === ''
                    ? []
                    : [(string) $key => (float) ($subcriterion->NU_PESO_SUBCRITERIO ?? 0)];
            })
            ->sum();
    }

    public function calculateCertificationScore(iterable|int $certifications): float
    {
        $quantity = is_int($certifications) ? $certifications : collect($certifications)->count();

        return min(max($quantity, 0) * 100.0 / 5.0, 100.0);
    }

    public function calculateCompetencyScore(iterable $acquiredKnowledges, iterable $areaWeights): float
    {
        $weightsByArea = $this->areaWeightsByAreaId($areaWeights);

        return collect($acquiredKnowledges)
            ->filter(fn ($knowledge): bool => (int) $knowledge->TP_ADQUIRIDO === 1)
            ->sum(function ($knowledge) use ($weightsByArea): float {
                $competency = $knowledge->competency ?? null;

                if ($competency === null || $competency->FK_AREA_CONHECIMENTO === null) {
                    return 0.0;
                }

                $areaWeight = $weightsByArea->get((int) $competency->FK_AREA_CONHECIMENTO, 0.0);

                return $areaWeight * ($this->competencyLevelWeight($competency->difficulty ?? null) / 5.0);
            });
    }

    private function calculateExperienceScore(ProfessionalExperience|string|null $experience, bool $allowNone): float
    {
        if ($experience === null) {
            return 0.0;
        }

        foreach ($this->experienceLabels($experience) as $label) {
            $normalized = $this->normalizeLabel($label);

            if (in_array($normalized, self::NONE_EXPERIENCE_LABELS, true)) {
                if ($allowNone) {
                    return 0.0;
                }

                throw new InvalidArgumentException('Experiencia "Nenhum" nao permitida para atuacao no perfil.');
            }

            if (array_key_exists($normalized, self::EXPERIENCE_SCORES)) {
                return self::EXPERIENCE_SCORES[$normalized];
            }
        }

        return 0.0;
    }

    private function areaWeightsByAreaId(iterable $areaWeights): Collection
    {
        return collect($areaWeights)
            ->filter(fn ($weight): bool => $weight->FK_AREA_CONHECIMENTO !== null)
            ->mapWithKeys(fn ($weight): array => [
                (int) $weight->FK_AREA_CONHECIMENTO => (float) ($weight->NU_PESO_CRIT_COMPETENCIA ?? 0),
            ]);
    }

    private function competencyLevelWeight(mixed $difficulty): float
    {
        foreach ([$difficulty?->NO_DIFICULDADE_PT, $difficulty?->NO_DIFICULDADE_EN] as $label) {
            $normalized = $this->normalizeLabel($label);

            if (array_key_exists($normalized, self::COMPETENCY_LEVEL_WEIGHTS)) {
                return self::COMPETENCY_LEVEL_WEIGHTS[$normalized];
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
