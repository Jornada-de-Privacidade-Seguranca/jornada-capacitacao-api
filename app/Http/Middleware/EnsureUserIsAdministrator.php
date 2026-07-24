<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsureUserIsAdministrator
{
    private const ADMIN_USER_TYPE = 1;

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error(
                message: 'Usuario nao autenticado.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'UNAUTHENTICATED',
            );
        }

        if ((int) $user->FK_TIPO_USUARIO !== self::ADMIN_USER_TYPE) {
            return ApiResponse::error(
                message: 'Permissao administrativa obrigatoria.',
                status: Response::HTTP_FORBIDDEN,
                code: 'ADMIN_PERMISSION_REQUIRED',
            );
        }

        return $next($request);
    }
}
