<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    protected $fillable = [
        'title',
        'content',
        'image',
        'url',
        'views_count',
        'likes',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'likes' => 'integer',
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

}
