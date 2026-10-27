<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListJourneyProfileRequest;
use App\Http\Resources\JourneyProfileResource;
use App\Services\JourneyProfileService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class JourneyProfileController
{
    public function __construct(private readonly JourneyProfileService $journeyProfileService) {}

    public function index(ListJourneyProfileRequest $request): JsonResponse
    {
        $paginator = $this->journeyProfileService->list(
            page: $request->page(),
            perPage: $request->perPage(),
            modality: $request->modality(),
        );

        return ApiResponse::paginated(
            resource: JourneyProfileResource::collection($paginator)->resolve(),
            paginator: $paginator,
            message: 'Journey profiles retrieved successfully.',
        );
    }

    public function show(int $id): JsonResponse
    {
        try {
            return ApiResponse::success(
                data: (new JourneyProfileResource($this->journeyProfileService->find($id)))->resolve(),
                message: 'Journey profile retrieved successfully.',
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Journey profile not found.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_PROFILE_NOT_FOUND',
            );
        }
    }
}
