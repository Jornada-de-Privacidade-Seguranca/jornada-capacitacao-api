<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCompetencyRequest;
use App\Http\Requests\UpdateCompetencyRequest;
use App\Http\Resources\CompetencyResource;
use App\Services\CompetencyService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

// Controller temporario de teste para exercitar o CRUD de competencias.
// Remover junto com a rota /competencies quando os endpoints oficiais de
// competencias forem implementados.
class CompetencyController
{
    public function __construct(private readonly CompetencyService $competencyService)
    {
    }

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            data: CompetencyResource::collection($this->competencyService->list())->resolve(),
            message: 'Competencies retrieved successfully.',
        );
    }

    public function store(StoreCompetencyRequest $request): JsonResponse
    {
        return ApiResponse::success(
            data: (new CompetencyResource($this->competencyService->create($request->validated())))->resolve(),
            message: 'Competency created successfully.',
            status: Response::HTTP_CREATED,
        );
    }

    public function show(int $id): JsonResponse
    {
        try {
            return ApiResponse::success(
                data: (new CompetencyResource($this->competencyService->find($id)))->resolve(),
                message: 'Competency retrieved successfully.',
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Competency not found.',
                status: Response::HTTP_NOT_FOUND,
                code: 'COMPETENCY_NOT_FOUND',
            );
        }
    }

    public function update(UpdateCompetencyRequest $request, int $id): JsonResponse
    {
        try {
            return ApiResponse::success(
                data: (new CompetencyResource($this->competencyService->update($id, $request->validated())))->resolve(),
                message: 'Competency updated successfully.',
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Competency not found.',
                status: Response::HTTP_NOT_FOUND,
                code: 'COMPETENCY_NOT_FOUND',
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->competencyService->delete($id);
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Competency not found.',
                status: Response::HTTP_NOT_FOUND,
                code: 'COMPETENCY_NOT_FOUND',
            );
        }

        return ApiResponse::success(
            data: null,
            message: 'Competency deleted successfully.',
        );
    }
}
