<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCompetencyRequest;
use App\Http\Requests\UpdateCompetencyRequest;
use App\Services\CompetencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CompetencyController
{
    public function __construct(private readonly CompetencyService $competencyService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->competencyService->list(),
        ]);
    }

    public function store(StoreCompetencyRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->competencyService->create($request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->competencyService->find($id),
        ]);
    }

    public function update(UpdateCompetencyRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->competencyService->update($id, $request->validated()),
        ]);
    }

    public function destroy(int $id): Response
    {
        $this->competencyService->delete($id);

        return response()->noContent();
    }
}
