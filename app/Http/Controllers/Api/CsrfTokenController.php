<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsrfTokenController
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'csrf_token' => $request->session()->token(),
            ],
            message: 'Token CSRF gerado com sucesso.',
        );
    }
}
