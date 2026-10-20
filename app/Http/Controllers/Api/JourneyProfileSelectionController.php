<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\JourneyProfileSelectionException;
use App\Http\Requests\SelectJourneyProfileRequest;
use App\Http\Resources\SelectedJourneyProfileResource;
use App\Services\JourneyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class JourneyProfileSelectionController
{
    public function __construct(private readonly JourneyService $journeyService) {}

    public function update(SelectJourneyProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->journeyService->selectInitialJourneyProfile(
                authenticatedUser: $request->user(),
                journeyProfileId: (int) $request->validated('journey_profile_id'),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Perfil de jornada nao encontrado.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_PROFILE_NOT_FOUND',
            );
        } catch (JourneyProfileSelectionException $exception) {
            if ($exception->reason !== JourneyProfileSelectionException::LOCKED) {
                throw $exception;
            }

            return ApiResponse::error(
                message: 'O perfil inicial nao pode ser alterado depois de iniciar uma jornada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_PROFILE_SELECTION_LOCKED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel salvar o perfil de jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_PROFILE_SELECTION_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new SelectedJourneyProfileResource($user))->resolve(),
            message: 'Perfil de jornada selecionado com sucesso.',
        );
    }
}
