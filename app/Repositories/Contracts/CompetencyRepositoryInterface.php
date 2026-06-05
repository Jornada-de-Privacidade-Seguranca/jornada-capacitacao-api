<?php

namespace App\Repositories\Contracts;

use App\Models\Competency;
use Illuminate\Database\Eloquent\Collection;

interface CompetencyRepositoryInterface
{
    public function all(): Collection;

    public function create(array $data): Competency;

    public function findOrFail(int $id): Competency;

    public function update(int $id, array $data): Competency;

    public function delete(int $id): void;
}
