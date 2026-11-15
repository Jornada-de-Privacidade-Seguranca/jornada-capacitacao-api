<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AdminResultFilterRequest;
use App\Http\Requests\ListAdminResultsRequest;
use App\Http\Resources\AdminResultMetricsResource;
use App\Http\Resources\AdminResultResource;
use App\Http\Resources\UserWithResultsResource;
use App\Services\AdminResultService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class AdminResultController
{
    public function __construct(private readonly AdminResultService $adminResultService) {}

    public function index(ListAdminResultsRequest $request): JsonResponse
    {
        try {
            $results = $this->adminResultService->listCompletedResults(
                filters: $request->validated(),
                page: (int) $request->validated('page'),
                perPage: (int) $request->validated('per_page'),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os resultados no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'ADMIN_RESULTS_SEARCH_FAILED',
            );
        }

        return ApiResponse::paginated(
            resource: AdminResultResource::collection($results)->resolve(),
            paginator: $results,
            message: 'Resultados retornados com sucesso.',
        );
    }

    public function metrics(AdminResultFilterRequest $request): JsonResponse
    {
        try {
            $metrics = $this->adminResultService->getCompletedResultsMetrics($request->validated());
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar as metricas dos resultados no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'ADMIN_RESULTS_METRICS_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new AdminResultMetricsResource($metrics))->resolve(),
            message: 'Metricas dos resultados retornadas com sucesso.',
        );
    }

    public function departments(): JsonResponse
    {
        try {
            $departments = $this->adminResultService->listDepartments();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os setores no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'DEPARTMENTS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: $departments,
            message: 'Setores retornados com sucesso.',
        );
    }

    public function usersWithResults(): JsonResponse
    {
        try {
            $users = $this->adminResultService->listUsersWithCompletedResults();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar os usuarios com resultados no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USERS_WITH_RESULTS_SEARCH_FAILED',
            );
        }

        return ApiResponse::success(
            data: UserWithResultsResource::collection($users)->resolve(),
            message: 'Usuarios com resultados retornados com sucesso.',
        );
    }
}
