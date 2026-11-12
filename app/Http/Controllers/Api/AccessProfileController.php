<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LastAdministratorRequiredException;
use App\Exceptions\SelfAccountOperationNotAllowedException;
use App\Http\Controllers\Api\Concerns\ParsesUserIdentifier;
use App\Http\Requests\ChangeUserAccessProfileRequest;
use App\Http\Resources\AccessProfileHistoryResource;
use App\Http\Resources\AccessProfileResource;
use App\Http\Resources\UserManagementDetailResource;
use App\Services\UserManagementService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class AccessProfileController
{
    use ParsesUserIdentifier;

    public function __construct(private readonly UserManagementService $userManagementService) {}

    public function index(): JsonResponse
    {
        try {
            $profiles = $this->userManagementService->listAccessProfiles();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os perfis de acesso no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'ACCESS_PROFILES_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: AccessProfileResource::collection($profiles)->resolve(),
            message: 'Perfis de acesso retornados com sucesso.',
        );
    }

    public function changeProfile(ChangeUserAccessProfileRequest $request, string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $updatedUser = $this->userManagementService->changeAccessProfile(
                authenticatedUser: $request->user(),
                userId: $userId,
                newProfileTypeId: (int) $request->validated('profile_type_id'),
            );
        } catch (ModelNotFoundException) {
            return $this->userNotFoundResponse();
        } catch (SelfAccountOperationNotAllowedException) {
            return ApiResponse::error(
                message: 'Voce nao pode alterar o proprio perfil de acesso.',
                status: Response::HTTP_FORBIDDEN,
                code: 'SELF_PROFILE_CHANGE_FORBIDDEN',
            );
        } catch (LastAdministratorRequiredException) {
            return ApiResponse::error(
                message: 'O sistema deve manter ao menos um administrador ativo.',
                status: Response::HTTP_CONFLICT,
                code: 'LAST_ADMIN_REQUIRED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel alterar o perfil de acesso no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'ACCESS_PROFILE_CHANGE_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new UserManagementDetailResource($updatedUser))->resolve(),
            message: 'Perfil de acesso alterado com sucesso.',
        );
    }

    public function history(string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $history = $this->userManagementService->accessProfileHistory($userId);
        } catch (ModelNotFoundException) {
            return $this->userNotFoundResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar o historico de perfis no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'ACCESS_PROFILE_HISTORY_FAILED',
            );
        }

        return ApiResponse::success(
            data: AccessProfileHistoryResource::collection($history)->resolve(),
            message: 'Historico de alteracoes de perfil retornado com sucesso.',
        );
    }
}
