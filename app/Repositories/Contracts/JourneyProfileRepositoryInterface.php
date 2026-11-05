<?php

namespace App\Repositories\Contracts;

use App\Models\JourneyProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JourneyProfileRepositoryInterface
{
    public function all(int $page, int $perPage, ?string $modality = null, ?string $search = null): LengthAwarePaginator;

    public function findOrFail(int $id): JourneyProfile;
}
