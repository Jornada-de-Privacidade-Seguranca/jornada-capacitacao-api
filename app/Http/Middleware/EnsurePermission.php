<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): SymfonyResponse
    {
        if (! $request->user()?->hasPermission($permission)) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para executar esta acao.',
                status: Response::HTTP_FORBIDDEN,
                code: 'PERMISSION_DENIED',
            );
        }

        return $next($request);
    }
}
