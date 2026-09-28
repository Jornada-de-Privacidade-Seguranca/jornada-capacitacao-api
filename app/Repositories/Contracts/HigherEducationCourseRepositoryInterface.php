<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface HigherEducationCourseRepositoryInterface
{
    public function listAvailable(?int $typeId): Collection;
}
