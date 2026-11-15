<?php

namespace App\Repositories\Contracts;

use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminResultRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedCompletedResults(array $filters, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCompletedResultsMetrics(array $filters): array;

    /**
     * @return list<string>
     */
    public function listDepartments(): array;

    /**
     * @return Collection<int, Usuario>
     */
    public function listUsersWithCompletedResults(): Collection;
}
