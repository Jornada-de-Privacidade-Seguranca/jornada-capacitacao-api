<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IncompleteJourneyException;
use App\Exceptions\JourneyProfileNotFoundException;
use App\Exceptions\OngoingJourneyExistsException;
use App\Http\Requests\ListUserJourneysRequest;
use App\Http\Requests\ShowJourneyRequest;
use App\Http\Requests\StoreUserJourneyRequest;
use App\Http\Resources\JourneyDetailResource;
use App\Http\Resources\JourneyListResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreUserJourneyRequest $request, int $user): JsonResponse
    {
        try {
            $journey = $this->journeyService->startForUser(
                authenticatedUser: $request->user(),
                userId: $user,
                data: $request->validated(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Usuario nao encontrado.',
                status: Response::HTTP_NOT_FOUND,
                code: 'USER_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para iniciar uma jornada para este usuario.',
                status: Response::HTTP_FORBIDDEN,
                code: 'USER_JOURNEYS_FORBIDDEN',
            );
        } catch (JourneyProfileNotFoundException) {
            return ApiResponse::error(
                message: 'Perfil de jornada nao encontrado.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_PROFILE_NOT_FOUND',
            );
        } catch (OngoingJourneyExistsException) {
            return ApiResponse::error(
                message: 'Voce ja possui uma jornada em andamento. Finalize ou continue a jornada atual antes de iniciar uma nova.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_IN_PROGRESS',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel iniciar a jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_CREATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new JourneyListResource($journey))->resolve(),
            message: 'Jornada iniciada com sucesso.',
            status: Response::HTTP_CREATED,
        );
    }

    public function show(ShowJourneyRequest $request): JsonResponse
    {
        try {
            $journeyDetail = $this->journeyService->findForAuthenticatedUser(
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
                message: 'Voce nao possui permissao para visualizar esta jornada.',
                status: Response::HTTP_FORBIDDEN,
                code: 'JOURNEY_FORBIDDEN',
            );
        } catch (IncompleteJourneyException $exception) {
            return ApiResponse::error(
                message: 'Jornada concluida possui dados obrigatorios incompletos.',
                status: Response::HTTP_CONFLICT,
                code: 'INCOMPLETE_JOURNEY',
                errors: [
                    'fields' => $exception->missingFields(),
                ],
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
            status: Response::HTTP_OK,
        );
    }
}
