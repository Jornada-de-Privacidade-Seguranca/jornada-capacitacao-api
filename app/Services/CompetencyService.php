<?php

namespace App\Services;

use App\Models\Competency;
use App\Repositories\Contracts\CompetencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompetencyService
{
    public function __construct(private readonly CompetencyRepositoryInterface $competencyRepository)
    {
    }

    public function list(): Collection
    {
        return $this->competencyRepository->all();
    }

    public function create(array $data): Competency
    {
        return $this->competencyRepository->create($data);
    }

    public function find(int $id): Competency
    {
        return $this->competencyRepository->findOrFail($id);
    }

    public function update(int $id, array $data): Competency
    {
        return $this->competencyRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->competencyRepository->delete($id);
    }
}
