<?php

namespace App\Services\Search;

use App\Models\Film;
use Illuminate\Database\Eloquent\Collection;

class FilmSearchService
{
  public function searchFilms(?string $searchQuery, int $limit = 10): Collection
  {
    if (empty($searchQuery) || trim($searchQuery) === '') {
      return Film::query()
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    }

    $searchTerm = '%' . $searchQuery . '%';
    $normalizedQuery = mb_strtolower($searchQuery);

    return Film::query()
      ->where(function ($q) use ($searchTerm, $normalizedQuery, $searchQuery) {
        $q->where('title', 'LIKE', $searchTerm)
          ->orWhere('code', 'LIKE', $searchTerm)
          ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(details, "$.*"))) LIKE ?', ["%{$normalizedQuery}%"])

          ->orWhereRaw('
            CHAR_LENGTH(title) - CHAR_LENGTH(REPLACE(LOWER(title), ?, "")) >= ? 
            AND ABS(CHAR_LENGTH(title) - CHAR_LENGTH(?)) <= 3
          ', [
            mb_substr($normalizedQuery, 0, 1),
            max(1, mb_strlen($normalizedQuery) - 2),
            $searchQuery
          ]);
      })
      ->orderByRaw('
                CASE 
                    WHEN LOWER(title) = ? THEN 1
                    WHEN LOWER(title) LIKE ? THEN 2
                    WHEN LOWER(title) LIKE ? THEN 3
                    ELSE 4
                END
            ', [$normalizedQuery, "{$normalizedQuery}%", "%{$normalizedQuery}%"])
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get();
  }
}
