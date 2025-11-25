<?php

namespace App\DTO;

readonly class StoryData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $content,
        public ?string $url,
        public ?string $imageUrl,
        public int $viewsCount,
        public int $likes,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(\App\Models\Story $story): self
    {
        return new self(
            id: $story->id,
            title: $story->title,
            content: $story->content,
            url: $story->url,
            imageUrl: $story->image_url ?? null,
            viewsCount: $story->views_count ?? 0,
            likes: $story->likes ?? 0,
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
            'url' => $this->url,
            'imageUrl' => $this->imageUrl,
            'viewsCount' => $this->viewsCount,
            'likes' => $this->likes,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
