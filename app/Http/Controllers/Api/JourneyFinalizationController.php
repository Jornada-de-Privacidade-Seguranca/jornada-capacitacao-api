<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IncompleteJourneyException;
use App\Exceptions\JourneyAlreadyFinishedException;
use App\Http\Resources\JourneyFinalizationResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class JourneyFinalizationController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $journey = $this->journeyService->finalizeCurrent($request->user());
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada atual nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_NOT_FOUND',
            );
        } catch (JourneyAlreadyFinishedException) {
            return ApiResponse::error(
                message: 'Esta jornada ja foi finalizada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_ALREADY_FINISHED',
            );
        } catch (IncompleteJourneyException $exception) {
            return ApiResponse::error(
                message: 'Existem etapas obrigatorias pendentes.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'INCOMPLETE_JOURNEY',
                errors: ['fields' => $exception->missingFields()],
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel finalizar a jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_FINALIZATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyFinalizationResource($journey),
            message: 'Jornada finalizada com sucesso.',
        );
    }
}
