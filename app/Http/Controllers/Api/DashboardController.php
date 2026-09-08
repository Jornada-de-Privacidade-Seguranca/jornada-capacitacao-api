<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: (new DashboardResource(
                $this->dashboardService->getDashboardData($request->user())
            ))->resolve(),
            message: 'Dados do painel recuperados com sucesso.',
        );
    }
}
