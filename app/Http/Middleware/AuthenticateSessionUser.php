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
        if (! Auth::check()) {
            return ApiResponse::error(
                message: 'Usuario nao autenticado.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'UNAUTHENTICATED',
            );
        }

        $user = $request->user();

        if ($user === null || (int) $user->ST_ATIVO !== self::STATUS_ACTIVE) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return ApiResponse::error(
                message: 'Sua conta ainda nao foi ativada por um administrador do sistema.',
                status: Response::HTTP_FORBIDDEN,
                code: 'ACCOUNT_NOT_ACTIVATED',
            );
        }

        return $next($request);
    }
}
