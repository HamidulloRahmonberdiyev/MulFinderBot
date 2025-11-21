<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Film extends Model
{
    protected $fillable = [
        'title',
        'message_id',
        'chat_id',
        'file_id',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Search films by title
     */
    public function scopeSearchByTitle($query, string $search)
    {
        return $query->where('title', 'like', "%{$search}%")
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get formatted film details
     */
    public function getFormattedDetails(): string
    {
        if (empty($this->details)) {
            return '';
        }

        $details = [];
        foreach ($this->details as $key => $value) {
            $emoji = $this->getEmojiForKey($key);
            $details[] = "{$emoji} <b>{$key}:</b> {$value}";
        }

        return implode("\n", $details);
    }

    /**
     * Get emoji for detail key
     */
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

    /**
     * Get short details for preview
     */
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
