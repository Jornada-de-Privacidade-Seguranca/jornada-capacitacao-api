<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\JourneyAlreadyFinishedException;
use App\Http\Requests\UpdateJourneyAnswersRequest;
use App\Http\Resources\JourneyDetailResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class JourneyAnswersController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function __invoke(UpdateJourneyAnswersRequest $request): JsonResponse
    {
        try {
            $journey = $this->journeyService->updateAnswers(
                authenticatedUser: $request->user(),
                journeyId: $request->validatedJourneyId(),
                data: $request->validated(),
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
        } catch (JourneyAlreadyFinishedException) {
            return ApiResponse::error(
                message: 'Jornada ja finalizada nao pode ser alterada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_ALREADY_FINISHED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel atualizar as respostas da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_ANSWERS_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyDetailResource($journey),
            message: 'Respostas da jornada atualizadas com sucesso.',
            status: Response::HTTP_OK,
        );
    }
}
