<?php

namespace App\DTO;

readonly class StoryData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $content,
        public ?string $imageUrl,
        public int $viewsCount,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(\App\Models\Story $story): self
    {
        $viewsCount = $story->views()->count();

        return new self(
            id: $story->id,
            title: $story->title,
            content: $story->content,
            imageUrl: $story->image_url ?? null,
            viewsCount: $viewsCount,
            createdAt: $story->created_at->toIso8601String(),
            updatedAt: $story->updated_at->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'imageUrl' => $this->imageUrl,
            'viewsCount' => $this->viewsCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
