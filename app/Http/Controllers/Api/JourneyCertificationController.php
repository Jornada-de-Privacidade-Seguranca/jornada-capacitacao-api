<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateJourneyCertificationRequest;
use App\Http\Resources\JourneyCertificationSelectionResource;
use App\Services\JourneyCertificationService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class JourneyCertificationController
{
    public function __construct(private readonly JourneyCertificationService $journeyCertificationService) {}

    public function show(Request $request): JsonResponse
    {
        try {
            $journey = $this->journeyCertificationService->findForAuthenticatedUser($request->user());
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFoundResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as certificacoes selecionadas no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_CERTIFICATIONS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyCertificationSelectionResource($journey),
            message: 'Certificacoes selecionadas encontradas com sucesso.',
        );
    }

    public function update(UpdateJourneyCertificationRequest $request): JsonResponse
    {
        try {
            $journey = $this->journeyCertificationService->replaceForAuthenticatedUser(
                authenticatedUser: $request->user(),
                certificationIds: $request->certificationIds(),
            );
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFoundResponse();
        } catch (ValidationException $exception) {
            return ApiResponse::error(
                message: 'Validation error.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'VALIDATION_ERROR',
                errors: $exception->errors(),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel atualizar as certificacoes selecionadas no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_CERTIFICATIONS_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyCertificationSelectionResource($journey),
            message: 'Certificacoes selecionadas encontradas com sucesso.',
        );
    }

    private function ongoingJourneyNotFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Nao existe jornada em andamento para este usuario.',
            status: Response::HTTP_NOT_FOUND,
            code: 'ONGOING_JOURNEY_NOT_FOUND',
        );
    }
}
