<?php

namespace App\Repositories\Eloquent;

use App\Models\Competency;
use App\Repositories\Contracts\CompetencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompetencyRepository implements CompetencyRepositoryInterface
{
    public function all(): Collection
    {
        return Competency::query()
            ->orderByDesc('PK_COMPETENCIAS')
            ->get();
    }

    public function create(array $data): Competency
    {
        return Competency::query()->create($data);
    }

    public function findOrFail(int $id): Competency
    {
        return Competency::query()->findOrFail($id);
    }

    public function update(int $id, array $data): Competency
    {
        $competency = $this->findOrFail($id);
        $competency->update($data);

        return $competency->refresh();
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
