<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ParsesUserIdentifier
{
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

    private function userNotFoundResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Usuario nao encontrado.',
            status: Response::HTTP_NOT_FOUND,
            code: 'USER_NOT_FOUND',
        );
    }
}
