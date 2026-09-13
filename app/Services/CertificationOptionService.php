<?php

namespace App\Services;

use App\Models\Usuario;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalCertificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CertificationOptionService
{
    public function __construct(
        private readonly JourneyRepositoryInterface $journeyRepository,
        private readonly ProfessionalCertificationRepositoryInterface $certificationRepository,
    ) {}

    public function listForAuthenticatedUser(Usuario $authenticatedUser): Collection
    {
        $ongoingJourney = $this->journeyRepository->findOngoingByUser((int) $authenticatedUser->PK_USUARIO);

        if ($ongoingJourney === null) {
            throw new ModelNotFoundException;
        }

        return $this->certificationRepository->allWithKnowledgeDomain();
    }
}
