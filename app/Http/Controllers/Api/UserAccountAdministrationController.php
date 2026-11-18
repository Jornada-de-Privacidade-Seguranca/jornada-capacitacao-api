<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidAccountStateException;
use App\Exceptions\LastAdministratorRequiredException;
use App\Exceptions\SelfAccountOperationNotAllowedException;
use App\Http\Requests\ListPendingUsersRequest;
use App\Http\Resources\AdministeredUserResource;
use App\Http\Resources\PendingUserActivationResource;
use App\Services\UserAccountAdministrationService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class UserAccountAdministrationController
{
    public function __construct(private readonly UserAccountAdministrationService $service) {}

    public function pending(ListPendingUsersRequest $request): JsonResponse
    {
        try {
            $users = $this->service->pendingActivationUsers(
                page: (int) $request->validated('page'),
                perPage: (int) $request->validated('per_page'),
            );
        } catch (AccessDeniedHttpException) {
            return $this->forbiddenResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as contas pendentes no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'PENDING_USERS_SEARCH_FAILED',
            );
        }

        return ApiResponse::paginated(
            resource: PendingUserActivationResource::collection($users)->resolve(),
            paginator: $users,
            message: 'Contas pendentes retornadas com sucesso.',
        );
    }

    public function activate(Request $request, string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $account = $this->service->activate($userId);
        } catch (AccessDeniedHttpException) {
            return $this->forbiddenResponse();
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse();
        } catch (InvalidAccountStateException) {
            return $this->invalidStateResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel ativar a conta no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_ACTIVATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new AdministeredUserResource($account))->resolve(),
            message: 'Conta ativada com sucesso.',
        );
    }

    public function deactivate(Request $request, string $user): JsonResponse
    {
        $userId = $this->parseUserId($user);

        if ($userId === null) {
            return $this->invalidUserIdentifierResponse();
        }

        try {
            $account = $this->service->deactivate($request->user(), $userId);
        } catch (AccessDeniedHttpException) {
            return $this->forbiddenResponse();
        } catch (SelfAccountOperationNotAllowedException) {
            return ApiResponse::error(
                message: 'Voce nao pode desativar a propria conta.',
                status: Response::HTTP_FORBIDDEN,
                code: 'SELF_DEACTIVATION_FORBIDDEN',
            );
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse();
        } catch (InvalidAccountStateException) {
            return $this->invalidStateResponse();
        } catch (LastAdministratorRequiredException) {
            return ApiResponse::error(
                message: 'O sistema deve manter ao menos um administrador ativo.',
                status: Response::HTTP_CONFLICT,
                code: 'LAST_ADMIN_REQUIRED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel desativar a conta no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_DEACTIVATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new AdministeredUserResource($account))->resolve(),
            message: 'Conta desativada com sucesso.',
        );
    }

    private function forbiddenResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Voce nao possui permissao para gerenciar contas de usuario.',
            status: Response::HTTP_FORBIDDEN,
            code: 'PERMISSION_DENIED',
        );
    }

    private function notFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Usuario nao encontrado.',
            status: Response::HTTP_NOT_FOUND,
            code: 'USER_NOT_FOUND',
        );
    }

    private function invalidStateResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'A conta nao esta em um estado que permita esta operacao.',
            status: Response::HTTP_CONFLICT,
            code: 'INVALID_USER_ACCOUNT_STATE',
        );
    }

    private function parseUserId(string $user): ?int
    {
        if (! ctype_digit($user)) {
            return null;
        }

        $normalized = ltrim($user, '0');

        if ($normalized === '' || strlen($normalized) > strlen((string) PHP_INT_MAX)) {
            return null;
        }

        if (strlen($normalized) === strlen((string) PHP_INT_MAX) && strcmp($normalized, (string) PHP_INT_MAX) > 0) {
            return null;
        }

        return (int) $normalized;
    }

    private function invalidUserIdentifierResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Identificador do usuario invalido.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_USER_IDENTIFIER',
            errors: [
                'user' => ['O identificador do usuario deve ser um numero inteiro maior ou igual a 1.'],
            ],
        );
    }
}
