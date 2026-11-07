<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SystemSummaryRequest;
use App\Http\Resources\SystemSummaryResource;
use App\Services\SystemSummaryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SystemSummaryController
{
    public function __construct(private readonly SystemSummaryService $systemSummaryService) {}

    public function __invoke(SystemSummaryRequest $request): JsonResponse
    {
        $data = $this->systemSummaryService->getSummary([
            'include_database_status' => $request->boolean('include_database_status', true),
            'tag' => $request->validated('tag'),
        ]);

        return ApiResponse::success(
            data: new SystemSummaryResource($data),
            message: 'System summary retrieved successfully.',
        );
    }
}
