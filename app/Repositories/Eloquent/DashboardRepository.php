<?php

namespace App\Repositories\Eloquent;

use App\Models\Journey;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function findUnfinishedJourney(int $userId): ?Journey
    {
        return $this->baseJourneyQuery($userId)
            ->whereNull('DH_FIM')
            ->first();
    }

    public function findLatestCompletedJourney(int $userId): ?Journey
    {
        return $this->baseJourneyQuery($userId)
            ->whereNotNull('DH_FIM')
            ->first();
    }

    public function findRecentCompletedJourneys(int $userId, ?int $exceptJourneyId, int $limit): Collection
    {
        return $this->baseJourneyQuery($userId)
            ->whereNotNull('DH_FIM')
            ->when($exceptJourneyId !== null, function (Builder $query) use ($exceptJourneyId): void {
                $query->where('PK_JORNADA', '<>', $exceptJourneyId);
            })
            ->limit($limit)
            ->get();
    }

    private function baseJourneyQuery(int $userId): Builder
    {
        return Journey::query()
            ->with([
                'journeyProfile.knowledgeDomain',
                'professionalExperience',
                'profileExperience',
                'englishProficiency',
                'higherEducationCourse',
                'postgraduateCourse',
            ])
            ->where('FK_USUARIO', $userId)
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA');
    }
}
