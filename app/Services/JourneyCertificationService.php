<?php

namespace App\Services;

use App\Models\Journey;
use App\Models\Usuario;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalCertificationRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JourneyCertificationService
{
    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly ProfessionalCertificationRepositoryInterface $certificationRepository,
    ) {}

    public function findForAuthenticatedUser(Usuario $authenticatedUser): Journey
    {
        $journey = $this->journeyRepository->findOngoingWithCertificationsByUser((int) $authenticatedUser->PK_USUARIO);

        if ($journey === null) {
            throw new ModelNotFoundException;
        }

        return $journey;
    }

    /**
     * @param  array<int, int>  $certificationIds
     */
    public function replaceForAuthenticatedUser(Usuario $authenticatedUser, array $certificationIds): Journey
    {
        $journey = $this->findForAuthenticatedUser($authenticatedUser);
        $uniqueCertificationIds = array_values(array_unique($certificationIds));

        $this->ensureCertificationsExist($uniqueCertificationIds);

        return DB::transaction(
            fn (): Journey => $this->journeyRepository->syncProfessionalCertifications($journey, $uniqueCertificationIds)
        );
    }

    /**
     * @param  array<int, int>  $certificationIds
     */
    private function ensureCertificationsExist(array $certificationIds): void
    {
        if ($this->certificationRepository->countExistingByIds($certificationIds) === count($certificationIds)) {
            return;
        }

        throw ValidationException::withMessages([
            'certification_ids' => ['A certificacao profissional informada nao existe.'],
        ]);
    }
}
