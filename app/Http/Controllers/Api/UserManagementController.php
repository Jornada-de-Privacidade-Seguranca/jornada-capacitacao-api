<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EmailAlreadyInUseException;
use App\Exceptions\LastAdministratorRequiredException;
use App\Exceptions\SelfAccountOperationNotAllowedException;
use App\Http\Controllers\Api\Concerns\ParsesUserIdentifier;
use App\Http\Requests\ListUsersRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserManagementDetailResource;
use App\Http\Resources\UserManagementListResource;
use App\Services\UserManagementService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class UserManagementController
{
    use ParsesUserIdentifier;

    public function __construct(private readonly UserManagementService $userManagementService) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        try {
            $users = $this->userManagementService->listUsers(
                filters: $request->validated(),
                page: (int) $request->validated('page'),
                perPage: (int) $request->validated('per_page'),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os usuarios no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USERS_SEARCH_FAILED',
            );
        }

        return ApiResponse::paginated(
            resource: UserManagementListResource::collection($users)->resolve(),
            paginator: $users,
            message: 'Usuarios retornados com sucesso.',
        );
    }

    public function show(string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $foundUser = $this->userManagementService->userDetail($userId);
        } catch (ModelNotFoundException) {
            return $this->userNotFoundResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar o usuario no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new UserManagementDetailResource($foundUser))->resolve(),
            message: 'Usuario retornado com sucesso.',
        );
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $updatedUser = $this->userManagementService->updateUser($userId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->userNotFoundResponse();
        } catch (EmailAlreadyInUseException) {
            return ApiResponse::error(
                message: 'Ja existe uma conta cadastrada com este e-mail.',
                status: Response::HTTP_CONFLICT,
                code: 'EMAIL_ALREADY_IN_USE',
                errors: ['email' => ['Informe outro e-mail.']],
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel atualizar o usuario no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_UPDATE_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new UserManagementDetailResource($updatedUser))->resolve(),
            message: 'Usuario atualizado com sucesso.',
        );
    }

    public function destroy(Request $request, string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $this->userManagementService->deleteUser($request->user(), $userId);
        } catch (ModelNotFoundException) {
            return $this->userNotFoundResponse();
        } catch (SelfAccountOperationNotAllowedException) {
            return ApiResponse::error(
                message: 'Voce nao pode excluir a propria conta.',
                status: Response::HTTP_FORBIDDEN,
                code: 'SELF_DELETION_FORBIDDEN',
            );
        } catch (LastAdministratorRequiredException) {
            return ApiResponse::error(
                message: 'O sistema deve manter ao menos um administrador ativo.',
                status: Response::HTTP_CONFLICT,
                code: 'LAST_ADMIN_REQUIRED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel excluir o usuario no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_DELETION_FAILED',
            );
        }

        return ApiResponse::success(
            data: null,
            message: 'Usuario excluido com sucesso.',
        );
    }
}
