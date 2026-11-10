<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\JourneyAlreadyFinishedException;
use App\Exceptions\JourneyNotInProgressException;
use App\Http\Requests\AttachJourneyCoursesRequest;
use App\Http\Resources\JourneyDetailResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class JourneyCoursesController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function __invoke(AttachJourneyCoursesRequest $request): JsonResponse
    {
        try {
            $journey = $this->journeyService->attachCourses(
                authenticatedUser: $request->user(),
                journeyId: $request->validatedJourneyId(),
                courseIds: $request->courseIds(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para atualizar esta jornada.',
                status: Response::HTTP_FORBIDDEN,
                code: 'PERMISSION_DENIED',
            );
        } catch (JourneyNotInProgressException) {
            return ApiResponse::error(
                message: 'Jornada cancelada nao pode ser alterada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_NOT_IN_PROGRESS',
            );
        } catch (JourneyAlreadyFinishedException) {
            return ApiResponse::error(
                message: 'Jornada ja finalizada nao pode ser alterada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_ALREADY_FINISHED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel vincular os cursos a jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_COURSES_ATTACH_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyDetailResource($journey),
            message: 'Cursos vinculados a jornada com sucesso.',
            status: Response::HTTP_OK,
        );
    }
}
