<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuthenticatedUserResource;
use App\Support\ApiResponse;
use App\Support\SessionMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: (new AuthenticatedUserResource($request->user()))->resolve(),
            message: 'Usuario autenticado recuperado com sucesso.',
            meta: SessionMetadata::toArray(),
        );
    }
}
