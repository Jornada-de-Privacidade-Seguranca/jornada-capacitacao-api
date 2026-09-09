<?php

namespace App\Services;

use App\Exceptions\GeneralDataOptionsNotConfiguredException;
use App\Exceptions\NoOngoingJourneyException;
use App\Models\Usuario;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\ProfessionalExperienceRepositoryInterface;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;

class JourneyGeneralDataOptionsService
{
    private const ENGLISH_CRITERION_NAME_PT = 'Nível de Proficiência - Inglês';

    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
        private readonly SubcriterionRepositoryInterface $subcriterionRepository,
        private readonly ProfessionalExperienceRepositoryInterface $professionalExperienceRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listForCurrentJourney(Usuario $authenticatedUser): array
    {
        $journey = $this->dashboardRepository->findUnfinishedJourney((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new NoOngoingJourneyException;
        }

        $englishLevels = $this->subcriterionRepository->allByCriterionNamePt(self::ENGLISH_CRITERION_NAME_PT);
        $experiences = $this->professionalExperienceRepository->allOrdered();

        if ($englishLevels->isEmpty() || $experiences->isEmpty()) {
            throw new GeneralDataOptionsNotConfiguredException;
        }

        return [
            'journey' => $journey,
            'english_levels' => $englishLevels,
            'professional_experiences' => $experiences,
            'profile_experiences' => $experiences,
        ];
    }
}
