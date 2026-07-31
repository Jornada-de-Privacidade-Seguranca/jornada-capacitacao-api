<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticateSessionUser
{
    private const STATUS_ACTIVE = 1;

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $guard = Auth::guard();
        $authenticatedUser = $guard->user();

        if ($authenticatedUser === null) {
            return ApiResponse::error(
                message: 'Usuario nao autenticado.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'UNAUTHENTICATED',
            );
        }

        $user = $guard->getProvider()->retrieveById($authenticatedUser->getAuthIdentifier());

        if ($user === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return ApiResponse::error(
                message: 'Usuario nao autenticado.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'UNAUTHENTICATED',
            );
        }

        if ((int) $user->ST_ATIVO !== self::STATUS_ACTIVE) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return ApiResponse::error(
                message: 'Sua conta ainda nao foi ativada por um administrador do sistema.',
                status: Response::HTTP_FORBIDDEN,
                code: 'ACCOUNT_NOT_ACTIVATED',
            );
        }

        $guard->setUser($user);

        return $next($request);
    }
}
