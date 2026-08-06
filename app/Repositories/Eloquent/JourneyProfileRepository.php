<?php

namespace App\Repositories\Eloquent;

use App\Models\JourneyProfile;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyProfileRepository implements JourneyProfileRepositoryInterface
{
    public function all(int $page, int $perPage): LengthAwarePaginator
    {
        return JourneyProfile::query()
            ->orderBy('PK_PERFIS_JORNADA')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function findOrFail(int $id): JourneyProfile
    {
        return JourneyProfile::query()->findOrFail($id);
    }
}
