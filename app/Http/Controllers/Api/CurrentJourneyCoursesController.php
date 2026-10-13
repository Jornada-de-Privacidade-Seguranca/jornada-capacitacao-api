<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ReplaceCurrentJourneyCoursesRequest;
use App\Http\Resources\HigherEducationCourseOptionResource;
use App\Services\CurrentJourneyCoursesService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CurrentJourneyCoursesController
{
    public function __construct(private readonly CurrentJourneyCoursesService $currentJourneyCoursesService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $journey = $this->currentJourneyCoursesService->getForAuthenticatedUser($request->user());
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFound();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os cursos selecionados no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'CURRENT_JOURNEY_COURSES_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: HigherEducationCourseOptionResource::collection($journey->higherEducationCourses)->resolve(),
            message: 'Cursos selecionados encontrados com sucesso.',
        );
    }

    public function update(ReplaceCurrentJourneyCoursesRequest $request): JsonResponse
    {
        try {
            $journey = $this->currentJourneyCoursesService->replaceForAuthenticatedUser(
                $request->user(),
                $request->courseIds(),
            );
        } catch (ModelNotFoundException) {
            return $this->ongoingJourneyNotFound();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel atualizar os cursos selecionados no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'CURRENT_JOURNEY_COURSES_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: HigherEducationCourseOptionResource::collection($journey->higherEducationCourses)->resolve(),
            message: 'Cursos selecionados atualizados com sucesso.',
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
