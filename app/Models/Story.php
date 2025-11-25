<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    protected $fillable = [
        'title',
        'content',
        'image',
        'views_count'
    ];

    protected $casts = [
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Story $story) {
            if ($story->image && Storage::disk('public')->exists($story->image)) {
                Storage::disk('public')->delete($story->image);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;

        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk('public');

        return $disk->url($this->image);
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function incrementViewsCount(): bool
    {
        try {
            $this->increment('views_count');
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to increment views count', [
                'story_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
