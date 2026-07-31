<?php

use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception) {
            return ApiResponse::error(
                message: 'Muitas tentativas. Tente novamente mais tarde.',
                status: Response::HTTP_TOO_MANY_REQUESTS,
                code: 'TOO_MANY_ATTEMPTS',
            );
        });

        $exceptions->render(function (TokenMismatchException $exception) {
            return ApiResponse::error(
                message: 'Token CSRF invalido.',
                status: 419,
                code: 'CSRF_TOKEN_MISMATCH',
            );
        });

        $exceptions->render(function (HttpException $exception) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            return ApiResponse::error(
                message: 'Token CSRF invalido.',
                status: 419,
                code: 'CSRF_TOKEN_MISMATCH',
            );
        });
    })
    ->create();
