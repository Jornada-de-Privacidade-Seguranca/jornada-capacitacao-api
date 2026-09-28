<?php

namespace App\Services;

use App\Exceptions\NoOngoingJourneyException;
use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\CurrentJourneyCompetencyRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurrentJourneyCompetencyService
{
    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly CurrentJourneyCompetencyRepositoryInterface $currentJourneyCompetencyRepository,
    ) {}

    public function listOptionsForAuthenticatedUser(Usuario $authenticatedUser): Collection
    {
        $journey = $this->ongoingJourneyFor($authenticatedUser);

        return $this->currentJourneyCompetencyRepository->optionsForProfile((int) $journey->FK_PERFIS_JORNADA);
    }

    public function answersForAuthenticatedUser(Usuario $authenticatedUser): array
    {
        $journey = $this->ongoingJourneyFor($authenticatedUser);

        return $this->buildAnswersPayload($journey);
    }

    public function replaceAnswersForAuthenticatedUser(Usuario $authenticatedUser, array $competencies): array
    {
        return DB::transaction(function () use ($authenticatedUser, $competencies): array {
            $journey = $this->lockedOngoingJourneyFor($authenticatedUser);

            $this->assertCompetenciesBelongToProfile(
                (int) $journey->FK_PERFIS_JORNADA,
                array_column($competencies, 'competency_id'),
            );

            $this->currentJourneyCompetencyRepository->replaceForJourney(
                (int) $journey->PK_JORNADA,
                $competencies,
            );

            return $this->buildAnswersPayload($journey);
        });
    }

    private function ongoingJourneyFor(Usuario $authenticatedUser): Journey
    {
        return $this->requireOngoingJourney(
            $this->journeyRepository->findOngoingByUser((int) $authenticatedUser->PK_USUARIO),
        );
    }

    private function lockedOngoingJourneyFor(Usuario $authenticatedUser): Journey
    {
        return $this->requireOngoingJourney(
            $this->journeyRepository->findOngoingByUserForUpdate((int) $authenticatedUser->PK_USUARIO),
        );
    }

    private function requireOngoingJourney(?Journey $journey): Journey
    {
        if ($journey === null) {
            throw new NoOngoingJourneyException;
        }

        return $journey;
    }

    private function buildAnswersPayload(Journey $journey): array
    {
        $journeyProfileId = (int) $journey->FK_PERFIS_JORNADA;
        $totalOptions = $this->currentJourneyCompetencyRepository->countOptionsForProfile($journeyProfileId);
        $answers = $this->currentJourneyCompetencyRepository->acquiredForJourney((int) $journey->PK_JORNADA, $journeyProfileId);

        return [
            'journey_id' => $journey->PK_JORNADA,
            'status' => 'ongoing',
            'is_complete' => $totalOptions > 0 && $answers->count() === $totalOptions,
            'competencies' => $answers,
        ];
    }

    private function assertCompetenciesBelongToProfile(int $journeyProfileId, array $competencyIds): void
    {
        $uniqueIds = array_values(array_unique(array_map('intval', $competencyIds)));
        $matchingCount = $this->currentJourneyCompetencyRepository->countMatchingOptionsForProfile($journeyProfileId, $uniqueIds);

        if ($matchingCount !== count($uniqueIds)) {
            throw ValidationException::withMessages([
                'competencies' => ['Cada competencia informada deve existir e pertencer ao perfil da jornada atual.'],
            ]);
        }
    }
}
