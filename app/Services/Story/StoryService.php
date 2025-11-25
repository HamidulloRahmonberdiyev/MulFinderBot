<?php

namespace App\Services\Story;

use App\DTO\StoryData;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Support\Facades\DB;

class StoryService
{
    public function incrementViewsCount(int $storyId, string $ipAddress): bool
    {
        try {
            $story = Story::findOrFail($storyId);

            return DB::transaction(function () use ($story, $storyId, $ipAddress) {
                $existingView = StoryView::where('story_id', $storyId)
                    ->where('ip_address', $ipAddress)
                    ->first();

                if ($existingView) return true;

                StoryView::create([
                    'story_id' => $storyId,
                    'ip_address' => $ipAddress,
                ]);

                $story->increment('views_count');

                return true;
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return false;
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') return true;
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLatestStories(int $limit = 10): array
    {
        $stories = Story::query()
            ->withCount('views')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $stories
            ->map(fn(Story $story) => StoryData::fromModel($story))
            ->toArray();
    }

    public function findById(int $id): ?StoryData
    {
        $story = Story::withCount('views')->find($id);

        if (!$story) {
            return null;
        }

        return StoryData::fromModel($story);
    }
}
