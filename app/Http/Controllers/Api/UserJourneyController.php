<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListUserJourneysRequest;
use App\Http\Resources\JourneyDetailResource;
use App\Http\Resources\JourneyListResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class UserJourneyController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function index(ListUserJourneysRequest $request, int $user): JsonResponse
    {
        try {
            $journeys = $this->journeyService->listForUser(
                authenticatedUser: $request->user(),
                userId: $user,
                filters: $request->validated(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Usuario nao encontrado.',
                status: Response::HTTP_NOT_FOUND,
                code: 'USER_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para visualizar as jornadas deste usuario.',
                status: Response::HTTP_FORBIDDEN,
                code: 'USER_JOURNEYS_FORBIDDEN',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as jornadas no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEYS_SEARCH_FAILED',
            );
        }

        return ApiResponse::paginated(
            resource: JourneyListResource::collection($journeys)->resolve(),
            paginator: $journeys,
            message: 'Jornadas retornadas com sucesso.',
        );
    }

    public function show(Request $request, string $journey): JsonResponse
    {
        if (! ctype_digit($journey) || (int) $journey < 1) {
            return ApiResponse::error(
                message: 'Identificador da jornada invalido.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'INVALID_JOURNEY_IDENTIFIER',
                errors: [
                    'journey' => [
                        'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
                    ],
                ],
            );
        }

        try {
            $journeyDetail = $this->journeyService->findForAuthenticatedUser(
                authenticatedUser: $request->user(),
                journeyId: (int) $journey,
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para visualizar esta jornada.',
                status: Response::HTTP_FORBIDDEN,
                code: 'JOURNEY_FORBIDDEN',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os dados da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: new JourneyDetailResource($journeyDetail),
            message: 'Jornada encontrada com sucesso.',
        );
    }
}
