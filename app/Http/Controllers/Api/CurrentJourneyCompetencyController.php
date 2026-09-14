<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ReplaceCurrentJourneyCompetenciesRequest;
use App\Http\Resources\CurrentJourneyCompetencyOptionResource;
use App\Http\Resources\CurrentJourneyCompetencyStateResource;
use App\Services\CurrentJourneyCompetencyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class CurrentJourneyCompetencyController
{
    public function __construct(private readonly CurrentJourneyCompetencyService $currentJourneyCompetencyService) {}

    public function options(Request $request): JsonResponse
    {
        try {
            $competencies = $this->currentJourneyCompetencyService->listOptionsForAuthenticatedUser($request->user());
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFound();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as competencias disponiveis no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'COMPETENCY_OPTIONS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: CurrentJourneyCompetencyOptionResource::collection($competencies)->resolve(),
            message: 'Competencias disponiveis encontradas com sucesso.',
        );
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $state = $this->currentJourneyCompetencyService->answersForAuthenticatedUser($request->user());
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFound();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as competencias da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_COMPETENCIES_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new CurrentJourneyCompetencyStateResource($state))->resolve(),
            message: 'Competencias da jornada encontradas com sucesso.',
        );
    }

    public function update(ReplaceCurrentJourneyCompetenciesRequest $request): JsonResponse
    {
        try {
            $state = $this->currentJourneyCompetencyService->replaceAnswersForAuthenticatedUser(
                $request->user(),
                $request->validated('competencies'),
            );
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFound();
        } catch (ValidationException $exception) {
            return ApiResponse::error(
                message: 'Validation error.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'VALIDATION_ERROR',
                errors: $exception->errors(),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel salvar as competencias da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_COMPETENCIES_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new CurrentJourneyCompetencyStateResource($state))->resolve(),
            message: 'Competencias da jornada encontradas com sucesso.',
        );
    }

    private function ongoingJourneyNotFound(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Nao existe jornada em andamento para este usuario.',
            status: Response::HTTP_NOT_FOUND,
            code: 'ONGOING_JOURNEY_NOT_FOUND',
        );
    }
}
