<?php

namespace App\Repositories\Eloquent;

use App\Models\Journey;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JourneyRepository implements JourneyRepositoryInterface
{
    public function findDetailedById(int $journeyId): ?Journey
    {
        return Journey::query()
            ->with([
                'usuario.profileType',
                'journeyProfile.knowledgeDomain',
                'journeyProfile.competencies.knowledgeDomain',
                'journeyProfile.competencies.knowledgeArea',
                'journeyProfile.competencies.difficulty',
                'professionalExperience',
                'profileExperience',
                'englishProficiency',
                'higherEducationCourse',
                'postgraduateCourse',
                'acquiredKnowledges.competency.knowledgeDomain',
                'acquiredKnowledges.competency.knowledgeArea',
                'acquiredKnowledges.competency.difficulty',
                'professionalCertifications.knowledgeDomain',
            ])
            ->where('PK_JORNADA', $journeyId)
            ->first();
    }

    public function paginatedByUser(int $userId, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Journey::query()
            ->with([
                'usuario',
                'journeyProfile.knowledgeDomain',
            ])
            ->withCount([
                'acquiredKnowledges',
                'professionalCertifications',
            ])
            ->where('FK_USUARIO', $userId);

        if (($filters['status'] ?? null) === 'completed') {
            $query->whereNotNull('DH_FIM');
        }

        if (($filters['status'] ?? null) === 'ongoing') {
            $query->whereNull('DH_FIM');
        }

        if (isset($filters['journey_profile_id'])) {
            $query->where('FK_PERFIS_JORNADA', $filters['journey_profile_id']);
        }

        if (isset($filters['started_from'])) {
            $query->whereDate('DH_CRIADO_EM', '>=', $filters['started_from']);
        }

        if (isset($filters['started_to'])) {
            $query->whereDate('DH_CRIADO_EM', '<=', $filters['started_to']);
        }

        if (isset($filters['ended_from'])) {
            $query->whereDate('DH_FIM', '>=', $filters['ended_from']);
        }

        if (isset($filters['ended_to'])) {
            $query->whereDate('DH_FIM', '<=', $filters['ended_to']);
        }

        return $query
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function hasOngoingJourney(int $userId): bool
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->exists();
    }

    public function create(array $data): Journey
    {
        return Journey::query()->create($data);
    }
}
