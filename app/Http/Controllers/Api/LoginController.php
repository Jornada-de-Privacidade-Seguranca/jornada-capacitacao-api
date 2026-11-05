<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InactiveUserAccountException;
use App\Exceptions\InvalidLoginCredentialsException;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\LoginService;
use App\Support\ApiResponse;
use App\Support\SessionMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LoginController
{
    public function __construct(private readonly LoginService $loginService) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->loginService->authenticate($request->validated());
        } catch (InvalidLoginCredentialsException) {
            return ApiResponse::error(
                message: 'Usuario e/ou senha invalidos.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'INVALID_CREDENTIALS',
            );
        } catch (InactiveUserAccountException) {
            return ApiResponse::error(
                message: 'Sua conta ainda nao foi ativada por um administrador do sistema.',
                status: Response::HTTP_FORBIDDEN,
                code: 'ACCOUNT_NOT_ACTIVATED',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Erro interno do servidor.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'INTERNAL_SERVER_ERROR',
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return ApiResponse::success(
            data: (new AuthenticatedUserResource($user))->resolve(),
            message: 'Login realizado com sucesso.',
            meta: SessionMetadata::toArray(),
        );
    }
}
