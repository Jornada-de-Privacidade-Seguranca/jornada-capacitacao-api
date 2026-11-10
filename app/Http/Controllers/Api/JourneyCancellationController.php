<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\JourneyCancellationNotAllowedException;
use App\Http\Requests\CancelJourneyRequest;
use App\Http\Resources\JourneyCancellationResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class JourneyCancellationController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function __invoke(CancelJourneyRequest $request): JsonResponse
    {
        try {
            $journey = $this->journeyService->cancelForUser(
                authenticatedUser: $request->user(),
                journeyId: $request->validatedJourneyId(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para cancelar esta jornada.',
                status: Response::HTTP_FORBIDDEN,
                code: 'PERMISSION_DENIED',
            );
        } catch (JourneyCancellationNotAllowedException) {
            return ApiResponse::error(
                message: 'Esta jornada nao pode ser cancelada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_CANCELLATION_NOT_ALLOWED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel cancelar a jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_CANCELLATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyCancellationResource($journey),
            message: 'Jornada cancelada com sucesso.',
            status: Response::HTTP_OK,
        );
    }
}
