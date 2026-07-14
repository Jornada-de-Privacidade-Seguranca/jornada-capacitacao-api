<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TemplateExampleRequest;
use App\Http\Resources\TemplateExampleResource;
use App\Services\TemplateExampleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TemplateExampleController
{
    public function __construct(private readonly TemplateExampleService $templateExampleService)
    {
    }

    public function __invoke(TemplateExampleRequest $request): JsonResponse
    {
        // Controllers devem ser finos: pegar dados validados, chamar Service
        // e retornar pelo contrato padrao da API.
        $data = $this->templateExampleService->show(
            context: $request->validated('context'),
            includeSteps: $request->boolean('include_steps', true),
        );

        return ApiResponse::success(
            data: new TemplateExampleResource($data),
            message: 'Template endpoint retrieved successfully.',
        );
    }
}
