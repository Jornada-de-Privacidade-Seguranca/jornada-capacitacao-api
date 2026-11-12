<?php

namespace App\Repositories\Contracts;

use App\Models\ProfileType;
use App\Models\ProfileTypeChange;
use Illuminate\Support\Collection;

interface AccessProfileRepositoryInterface
{
    /**
     * @return Collection<int, ProfileType>
     */
    public function allWithPermissions(): Collection;

    public function findOrFail(int $profileTypeId): ProfileType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordProfileChange(array $data): ProfileTypeChange;

    /**
     * @return Collection<int, ProfileTypeChange>
     */
    public function historyForUser(int $userId): Collection;
}
