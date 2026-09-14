<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateCurrentJourneyGeneralDataRequest;
use App\Http\Resources\CurrentJourneyGeneralDataResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class CurrentJourneyGeneralDataController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function options(Request $request): JsonResponse
    {
        try {
            $options = $this->journeyService->getCurrentGeneralDataOptions($request->user());
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada em andamento nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'CURRENT_JOURNEY_NOT_FOUND',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as opcoes dos dados gerais da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'CURRENT_JOURNEY_GENERAL_DATA_OPTIONS_FAILED',
            );
        }

        return ApiResponse::success(
            data: $options,
            message: 'Opcoes dos dados gerais da jornada retornadas com sucesso.',
        );
    }

    public function update(UpdateCurrentJourneyGeneralDataRequest $request): JsonResponse
    {
        try {
            $journey = $this->journeyService->updateCurrentGeneralData(
                authenticatedUser: $request->user(),
                data: $request->validated(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada em andamento nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'CURRENT_JOURNEY_NOT_FOUND',
            );
        } catch (ValidationException $exception) {
            return ApiResponse::error(
                message: 'Validation error.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'VALIDATION_ERROR',
                errors: $exception->errors(),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel salvar os dados gerais da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'CURRENT_JOURNEY_GENERAL_DATA_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: new CurrentJourneyGeneralDataResource($journey),
            message: 'Dados gerais da jornada salvos com sucesso.',
        );
    }
}
