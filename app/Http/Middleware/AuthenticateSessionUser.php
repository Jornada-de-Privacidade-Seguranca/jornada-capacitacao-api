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
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! Auth::check()) {
            return ApiResponse::error(
                message: 'Usuario nao autenticado.',
                status: Response::HTTP_UNAUTHORIZED,
                code: 'UNAUTHENTICATED',
            );
        }

        return $next($request);
    }
}
