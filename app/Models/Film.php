<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Film extends Model
{
    protected $fillable = [
        'title',
        'description',
        'source_type',
        'video_url',
        'code',
        'message_id',
        'chat_id',
        'file_id',
        'details',
        'downloads',
        'description',
        'video_url',
        'source_type'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeSearchByTitle($query, string $search)
    {
        return $query->where('title', 'like', "%{$search}%")
            ->orderBy('created_at', 'desc');
    }

    public function scopeSourceType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    public function getFormattedDetails(): string
    {
        if (empty($this->details)) return '';

        $details = [];
        foreach ($this->details as $key => $value) {
            $emoji = $this->getEmojiForKey($key);
            $details[] = "{$emoji} <b>{$key}:</b> {$value}";
        }

        return implode("\n", $details);
    }

    private function getEmojiForKey(string $key): string
    {
        return match ($key) {
            'Janr' => '🎭',
            'Sifat' => '📺',
            'Davomiyligi' => '⏱',
            'Davlat' => '🌍',
            'Yil' => '📅',
            'Rejissyor' => '🎬',
            'Til' => '🗣',
            default => '📌'
        };
    }

    public function getShortDetails(): string
    {
        $parts = [];

        if (isset($this->details['Janr'])) {
            $parts[] = $this->details['Janr'];
        }

        if (isset($this->details['Sifat'])) {
            $parts[] = $this->details['Sifat'];
        }

        return !empty($parts) ? ' • ' . implode(' • ', $parts) : '';
    }
}
