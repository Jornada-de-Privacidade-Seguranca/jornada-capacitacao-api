<?php

namespace App\Services;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyProfileService
{
    public function __construct(private readonly JourneyProfileRepositoryInterface $journeyProfileRepository)
    {
    }

    public function list(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->journeyProfileRepository->all($page, $perPage);
    }

    public function find(int $id): JourneyProfile
    {
        return $this->journeyProfileRepository->findOrFail($id);
    }
}
