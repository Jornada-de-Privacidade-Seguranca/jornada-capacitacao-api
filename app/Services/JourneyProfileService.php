<?php

namespace App\Services;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyProfileService
{
    public function __construct(private readonly JourneyProfileRepositoryInterface $journeyProfileRepository) {}

    public function list(
        int $page,
        int $perPage,
        ?string $modality = null,
        ?string $search = null,
        ?array $domainIds = null,
        ?array $userStatuses = null,
        ?int $userId = null,
    ): LengthAwarePaginator {
        return $this->journeyProfileRepository->all($page, $perPage, $modality, $search, $domainIds, $userStatuses, $userId);
    }

    public function find(int $id): JourneyProfile
    {
        return $this->journeyProfileRepository->findOrFail($id);
    }

    public function findWithCounts(int $id): JourneyProfile
    {
        return $this->journeyProfileRepository->findWithCountsOrFail($id);
    }
}
