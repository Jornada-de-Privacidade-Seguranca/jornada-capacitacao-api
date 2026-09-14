<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CurrentJourneyCompetencyRepositoryInterface
{
    public function optionsForProfile(int $journeyProfileId): Collection;

    public function acquiredForJourney(int $journeyId, int $journeyProfileId): Collection;

    public function countOptionsForProfile(int $journeyProfileId): int;

    public function countMatchingOptionsForProfile(int $journeyProfileId, array $competencyIds): int;

    public function replaceForJourney(int $journeyId, array $competencies): void;
}
