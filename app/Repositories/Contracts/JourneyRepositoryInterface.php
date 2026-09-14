<?php

namespace App\Repositories\Contracts;

use App\Models\Journey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JourneyRepositoryInterface
{
    public function paginatedByUser(int $userId, array $filters, int $page, int $perPage): LengthAwarePaginator;

    public function findDetailedById(int $journeyId): ?Journey;

    public function findOngoingByUser(int $userId): ?Journey;

    public function findOngoingWithCertificationsByUser(int $userId): ?Journey;

    /**
     * @param  array<int, int>  $certificationIds
     */
    public function syncProfessionalCertifications(Journey $journey, array $certificationIds): Journey;
}
