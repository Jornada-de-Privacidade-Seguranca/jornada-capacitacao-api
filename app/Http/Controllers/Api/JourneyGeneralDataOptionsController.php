<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralDataOptionsNotConfiguredException;
use App\Exceptions\NoOngoingJourneyException;
use App\Http\Resources\JourneyGeneralDataOptionsResource;
use App\Services\JourneyGeneralDataOptionsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class JourneyGeneralDataOptionsController
{
    public function __construct(private readonly JourneyGeneralDataOptionsService $journeyGeneralDataOptionsService) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $options = $this->journeyGeneralDataOptionsService->listForCurrentJourney($request->user());
        } catch (NoOngoingJourneyException) {
            return ApiResponse::error(
                message: 'Jornada atual nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'ONGOING_JOURNEY_NOT_FOUND',
            );
        } catch (GeneralDataOptionsNotConfiguredException) {
            return ApiResponse::error(
                message: 'Opcoes de dados gerais nao configuradas.',
                status: Response::HTTP_CONFLICT,
                code: 'GENERAL_DATA_OPTIONS_NOT_CONFIGURED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as opcoes de dados gerais no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'GENERAL_DATA_OPTIONS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new JourneyGeneralDataOptionsResource($options))->resolve(),
            message: 'Opcoes de dados gerais encontradas com sucesso.',
        );
    }
}
