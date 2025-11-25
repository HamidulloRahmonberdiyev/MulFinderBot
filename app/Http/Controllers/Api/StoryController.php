<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Story\StoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function incrementViews(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required|integer|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $storyId = (int) $request->input('story_id');
            $ipAddress = $this->getClientIpAddress($request);

            $success = $this->storyService->incrementViewsCount($storyId, $ipAddress);

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

    private function getClientIpAddress(Request $request): string
    {
        $ipAddress = $request->ip();

        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            $ipAddress = trim($ips[0]);
        }

        $realIp = $request->header('X-Real-IP');
        if ($realIp) {
            $ipAddress = trim($realIp);
        }

        return $ipAddress ?: '0.0.0.0';
    }
}
