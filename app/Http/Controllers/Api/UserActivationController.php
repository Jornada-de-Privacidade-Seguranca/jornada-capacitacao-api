<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\UserNotFoundException;
use App\Http\Resources\UserActivationResource;
use App\Services\UserActivationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserActivationController
{
    public function __construct(private readonly UserActivationService $userActivationService)
    {
    }

    public function __invoke(int $user): JsonResponse
    {
        try {
            $activatedUser = $this->userActivationService->activate($user);
        } catch (UserNotFoundException) {
            return ApiResponse::error(
                message: 'Usuario nao encontrado.',
                status: Response::HTTP_NOT_FOUND,
                code: 'USER_NOT_FOUND',
            );
        }

        return ApiResponse::success(
            data: (new UserActivationResource($activatedUser))->resolve(),
            message: 'Usuario ativado com sucesso.',
        );
    }
}
