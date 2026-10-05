<?php

namespace App\Repositories\Contracts;

use App\Models\Journey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JourneyRepositoryInterface
{
    public function paginatedByUser(int $userId, array $filters, int $page, int $perPage): LengthAwarePaginator;

    public function hasOngoingJourney(int $userId): bool;

    public function create(array $data): Journey;

    public function findDetailedById(int $journeyId): ?Journey;

    public function findDetailedByIdForUpdate(int $journeyId): ?Journey;

    public function update(Journey $journey, array $data): void;

    public function attachCourses(Journey $journey, array $courseIds): void;

    public function attachCertifications(Journey $journey, array $certificationIds): void;

    public function upsertAcquiredKnowledges(Journey $journey, array $competencies): void;

    public function findOngoingByUser(int $userId): ?Journey;

    public function findLatestByUserForUpdate(int $userId): ?Journey;

    public function competencyIdsBelongToProfile(int $journeyProfileId, array $competencyIds): bool;
}
