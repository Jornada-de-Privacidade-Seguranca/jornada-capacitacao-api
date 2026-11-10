<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SystemSummaryRequest;
use App\Http\Resources\SystemSummaryResource;
use App\Services\SystemSummaryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class SystemSummaryController
{
    public function __construct(private readonly SystemSummaryService $systemSummaryService) {}

    public function __invoke(SystemSummaryRequest $request): JsonResponse
    {
        try {
            $data = $this->systemSummaryService->getSummary(
                authenticatedUser: $request->user(),
                options: [
                    'include_database_status' => $request->boolean('include_database_status', true),
                    'tag' => $request->validated('tag'),
                ],
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para visualizar o resumo do sistema.',
                status: Response::HTTP_FORBIDDEN,
                code: 'SYSTEM_SUMMARY_FORBIDDEN',
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel buscar o resumo do sistema no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'SYSTEM_SUMMARY_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new SystemSummaryResource($data))->resolve(),
            message: 'System summary retrieved successfully.',
        );
    }
}
