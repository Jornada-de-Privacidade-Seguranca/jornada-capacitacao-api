<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EmailAlreadyInUseException;
use App\Http\Requests\UpdateOwnProfileRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\OwnProfileService;
use App\Support\ApiResponse;
use App\Support\SessionMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthenticatedUserController
{
    public function __construct(private readonly OwnProfileService $ownProfileService) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: (new AuthenticatedUserResource($request->user()))->resolve(),
            message: 'Usuario autenticado recuperado com sucesso.',
            meta: SessionMetadata::toArray(),
        );
    }

    public function update(UpdateOwnProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->ownProfileService->update($request->user(), $request->validated());
        } catch (EmailAlreadyInUseException) {
            return ApiResponse::error(
                message: 'Ja existe uma conta cadastrada com este e-mail.',
                status: Response::HTTP_CONFLICT,
                code: 'EMAIL_ALREADY_IN_USE',
                errors: [
                    'email' => ['Informe outro e-mail.'],
                ],
            );
        }

        return ApiResponse::success(
            data: (new AuthenticatedUserResource($user))->resolve(),
            message: 'Dados do usuario atualizados com sucesso.',
            meta: SessionMetadata::toArray(),
        );
    }
}
