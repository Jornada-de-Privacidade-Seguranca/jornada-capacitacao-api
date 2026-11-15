<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\Contracts\AdminResultRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminResultService
{
    public function __construct(private readonly AdminResultRepositoryInterface $adminResultRepository) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listCompletedResults(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->adminResultRepository->paginatedCompletedResults($filters, $page, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCompletedResultsMetrics(array $filters): array
    {
        return $this->adminResultRepository->getCompletedResultsMetrics($filters);
    }

    /**
     * @return list<string>
     */
    public function listDepartments(): array
    {
        return $this->adminResultRepository->listDepartments();
    }

    /**
     * @return Collection<int, Usuario>
     */
    public function listUsersWithCompletedResults(): Collection
    {
        return $this->adminResultRepository->listUsersWithCompletedResults();
    }
}
