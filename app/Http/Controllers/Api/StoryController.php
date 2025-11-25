<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Story\StoryMetricRequest;
use App\Services\Story\StoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class StoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly StoryService $storyService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $stories = $this->storyService->getLatestStories(10);

            return $this->successResponse($stories, 'Stories retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve stories',
                500,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
        }
    }

    public function incrementViews(StoryMetricRequest $request): JsonResponse
    {
        try {
            $storyId = (int) $request->validated('story_id');
            $success = $this->storyService->incrementViewsCount($storyId);

            if (!$success) {
                return $this->errorResponse('Failed to increment views count', 500);
            }

            return $this->successResponse(null, 'Views count incremented successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to increment views count',
                500,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
        }
    }

    public function incrementLikes(StoryMetricRequest $request): JsonResponse
    {
        try {
            $storyId = (int) $request->validated('story_id');
            $success = $this->storyService->incrementLikesCount($storyId);

            if (!$success) {
                return $this->errorResponse('Failed to increment likes count', 500);
            }

            return $this->successResponse(null, 'Likes count incremented successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to increment likes count',
                500,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $story = $this->storyService->findById($id);

            if (!$story) {
                return $this->errorResponse('Story not found', 404);
            }

            return $this->successResponse($story->toArray(), 'Story retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve story',
                500,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
        }
    }
}
