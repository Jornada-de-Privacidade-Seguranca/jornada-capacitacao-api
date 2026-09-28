<?php

namespace App\Repositories\Eloquent;

use App\Models\HigherEducationCourse;
use App\Repositories\Contracts\HigherEducationCourseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HigherEducationCourseRepository implements HigherEducationCourseRepositoryInterface
{
    public function listAvailable(?int $typeId): Collection
    {
        return HigherEducationCourse::query()
            ->with('subcriterion')
            ->when($typeId !== null, function (Builder $query) use ($typeId): void {
                $query->where('FK_SUBCRITERIOS', $typeId);
            })
            ->orderBy('NO_CURSO_SUPERIOR_PT')
            ->get();
    }
}
