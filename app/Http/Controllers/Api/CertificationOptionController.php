<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CertificationOptionResource;
use App\Services\CertificationOptionService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CertificationOptionController
{
    public function __construct(private readonly CertificationOptionService $certificationOptionService) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $certifications = $this->certificationOptionService->listForAuthenticatedUser($request->user());
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Nao existe jornada em andamento para este usuario.',
                status: Response::HTTP_NOT_FOUND,
                code: 'ONGOING_JOURNEY_NOT_FOUND',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as certificacoes disponiveis no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'CERTIFICATION_OPTIONS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: CertificationOptionResource::collection($certifications)->resolve(),
            message: 'Certificacoes disponiveis encontradas com sucesso.',
        );
    }
}
