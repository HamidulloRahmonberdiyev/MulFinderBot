<?php

namespace App\Services\Story;

use App\DTO\StoryData;
use App\Models\Story;
class StoryService
{
    public function incrementViewsCount(int $storyId): bool
    {
        try {
            $updated = Story::whereKey($storyId)->increment('views_count');
            return $updated > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function incrementLikesCount(int $storyId): bool
    {
        try {
            $updated = Story::whereKey($storyId)->increment('likes');
            return $updated > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getLatestStories(int $limit = 10): array
    {
        $stories = Story::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $stories
            ->map(fn(Story $story) => StoryData::fromModel($story))
            ->toArray();
    }

    public function findById(int $id): ?StoryData
    {
        $story = Story::find($id);

        if (!$story) {
            return null;
        }

        return StoryData::fromModel($story);
    }
}
