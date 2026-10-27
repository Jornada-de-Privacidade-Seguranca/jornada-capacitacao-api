<?php

namespace App\Repositories\Eloquent;

use App\Models\AcquiredKnowledge;
use App\Models\Journey;
use App\Models\ProfileCompetency;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class JourneyRepository implements JourneyRepositoryInterface
{
    private const DETAILED_RELATIONS = [
        'usuario.profileType',
        'journeyProfile.knowledgeDomain',
        'journeyProfile.competencies.knowledgeDomain',
        'journeyProfile.competencies.knowledgeArea',
        'journeyProfile.competencies.difficulty',
        'professionalExperience',
        'profileExperience',
        'englishSubcriterion.criterion',
        'higherEducationCourses.subcriterion',
        'acquiredKnowledges.competency.knowledgeDomain',
        'acquiredKnowledges.competency.knowledgeArea',
        'acquiredKnowledges.competency.difficulty',
        'professionalCertifications.knowledgeDomain',
    ];

    public function findCurrentByUserId(int $userId): ?Journey
    {
        return Journey::query()
            ->with([
                'professionalExperience',
                'profileExperience',
                'englishSubcriterion',
            ])
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->first();
    }

    public function findDetailedById(int $journeyId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('PK_JORNADA', $journeyId)
            ->first();
    }

    public function findDetailedByIdForUpdate(int $journeyId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('PK_JORNADA', $journeyId)
            ->lockForUpdate()
            ->first();
    }

    public function findOngoingByUser(int $userId): ?Journey
    {
        return $this->ongoingByUserQuery($userId)->first();
    }

    public function findOngoingByUserForUpdate(int $userId): ?Journey
    {
        return $this->ongoingByUserQuery($userId)
            ->lockForUpdate()
            ->first();
    }

    private function ongoingByUserQuery(int $userId): Builder
    {
        return Journey::query()
            ->where('FK_USUARIO', $userId)
            ->whereNull('DH_FIM')
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA');
    }

    public function findLatestByUserForUpdate(int $userId): ?Journey
    {
        return Journey::query()
            ->with(self::DETAILED_RELATIONS)
            ->where('FK_USUARIO', $userId)
            ->orderByDesc('DH_CRIADO_EM')
            ->orderByDesc('PK_JORNADA')
            ->lockForUpdate()
            ->first();
    }

    public function syncProfessionalCertifications(Journey $journey, array $certificationIds): Journey
    {
        $journey->professionalCertifications()->sync($certificationIds);

        return $journey->load([
            'professionalCertifications' => fn ($query) => $query->orderBy(
                'TB_CERTIFICACAO_PROFISSIONAL.PK_CERTIFICACAO_PROFISSIONAL'
            ),
            'professionalCertifications.knowledgeDomain',
        ]);
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

    public function updateGeneralData(Journey $journey, array $data): Journey
    {
        $journey->fill([
            'FK_SUBCRITERIOS_INGLES' => $data['english_level_id'],
            'FK_EXPERIENCIA_PROFISSIONAL' => $data['professional_experience_id'],
            'FK_ATUACAO_PERFIL' => $data['profile_experience_id'],
        ]);
        $journey->save();

        return $journey->load([
            'professionalExperience',
            'profileExperience',
            'englishSubcriterion',
        ]);
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
        $journey = Journey::query()->create($data);

        return $journey->load(['usuario', 'journeyProfile.knowledgeDomain']);
    }

    public function update(Journey $journey, array $data): void
    {
        $journey->update($data);
    }

    public function attachCourses(Journey $journey, array $courseIds): void
    {
        $journey->higherEducationCourses()->syncWithoutDetaching($courseIds);
    }

    public function syncCourses(Journey $journey, array $courseIds): void
    {
        $journey->higherEducationCourses()->sync($courseIds);
    }

    public function attachCertifications(Journey $journey, array $certificationIds): void
    {
        $journey->professionalCertifications()->syncWithoutDetaching($certificationIds);
    }

    public function upsertAcquiredKnowledges(Journey $journey, array $competencies): void
    {
        foreach ($competencies as $competency) {
            AcquiredKnowledge::query()->updateOrCreate(
                [
                    'FK_JORNADA' => $journey->PK_JORNADA,
                    'FK_COMPETENCIAS' => $competency['competency_id'],
                ],
                [
                    'TP_ADQUIRIDO' => $competency['is_acquired'] ? 1 : 0,
                ],
            );
        }
    }

    public function competencyIdsBelongToProfile(int $journeyProfileId, array $competencyIds): bool
    {
        $distinctCompetencyIds = array_unique($competencyIds);

        $matchingCount = ProfileCompetency::query()
            ->where('FK_PERFIS_JORNADA', $journeyProfileId)
            ->whereIn('FK_COMPETENCIAS', $distinctCompetencyIds)
            ->distinct()
            ->count('FK_COMPETENCIAS');

        return $matchingCount === count($distinctCompetencyIds);
    }
}
