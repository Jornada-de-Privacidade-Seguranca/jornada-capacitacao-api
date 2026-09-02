<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoOngoingJourneyException;
use App\Http\Requests\ListJourneyCourseOptionsRequest;
use App\Http\Resources\HigherEducationCourseOptionResource;
use App\Services\JourneyCourseOptionsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class JourneyCourseOptionsController
{
    public function __construct(private readonly JourneyCourseOptionsService $journeyCourseOptionsService)
    {
    }

    public function __invoke(ListJourneyCourseOptionsRequest $request): JsonResponse
    {
        try {
            $courses = $this->journeyCourseOptionsService->listAvailableForCurrentJourney(
                authenticatedUser: $request->user(),
                typeId: $request->typeId(),
            );
        } catch (NoOngoingJourneyException) {
            return ApiResponse::error(
                message: 'Nao existe jornada em andamento para este usuario.',
                status: Response::HTTP_NOT_FOUND,
                code: 'ONGOING_JOURNEY_NOT_FOUND',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os cursos disponiveis no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'COURSE_OPTIONS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: HigherEducationCourseOptionResource::collection($courses)->resolve(),
            message: 'Cursos disponiveis encontrados com sucesso.',
        );
    }
}
