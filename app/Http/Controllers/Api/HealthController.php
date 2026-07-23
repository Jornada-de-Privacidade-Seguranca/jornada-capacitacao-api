<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

// Endpoint temporario de teste de disponibilidade enquanto a API real evolui.
// Remover ou substituir por um health check oficial quando definido.
class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'jornada-capacitacao-api',
        ]);
    }
}
