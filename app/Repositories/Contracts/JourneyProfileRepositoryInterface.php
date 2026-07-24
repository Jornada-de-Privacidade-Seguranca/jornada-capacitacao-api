<?php

namespace App\Repositories\Contracts;

use App\Models\JourneyProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JourneyProfileRepositoryInterface
{
    public function all(int $page, int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): JourneyProfile;
}