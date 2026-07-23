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
        return $this->competencyRepository->create($this->mapPayloadToColumns($data));
    }

    public function find(int $id): Competency
    {
        return $this->competencyRepository->findOrFail($id);
    }

    public function update(int $id, array $data): Competency
    {
        return $this->competencyRepository->update($id, $this->mapPayloadToColumns($data));
    }

    public function delete(int $id): void
    {
        $this->competencyRepository->delete($id);
    }

    private function mapPayloadToColumns(array $data): array
    {
        $columns = [];

        if (array_key_exists('name', $data)) {
            $columns['NO_COMPETENCIAS_PT'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $columns['DS_COMPETENCIAS_PT'] = $data['description'];
        }

        return $columns;
    }
}
