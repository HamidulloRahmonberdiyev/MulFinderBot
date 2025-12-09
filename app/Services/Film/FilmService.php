<?php

namespace App\Services\Film;

use App\DTO\FilmData;
use App\Models\Film;
use App\Services\Film\Search\FilmSearchService;
use App\Services\TelegramService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FilmService
{
  public function __construct(
    private readonly FilmSearchService $search,
    private readonly TelegramService   $telegram
  ) {}

  public function store(FilmData $filmData): ?Film
  {
    try {
      $film = Film::firstOrNew(
        ['message_id' => $filmData->messageId, 'chat_id' => $filmData->chatId]
      );

      $wasRecentlyCreated = !$film->exists;

      if ($wasRecentlyCreated) {
        $film->code = $this->generateNextCode();
      }

      $film->fill($filmData->toArray());
      $film->save();

      return $film;
    } catch (\Throwable $e) {
      $this->notifyError("❌ Film saqlanmadi: {$filmData->title}\n{$e->getMessage()}");
      Log::error('Film store failed', ['error' => $e]);
      return null;
    }
  }

  private function generateNextCode(): string
  {
    $maxNumber = Film::whereNotNull('code')
      ->where('code', 'like', 'C%')
      ->selectRaw('MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_code')
      ->value('max_code');

    return 'C' . $maxNumber + 1;
  }

  public function search(string $query): Collection
  {
    if (preg_match('/^\d+$/', $query)) {
      return Film::where('code', 'C' . $query)->limit(1)->get();
    }

    if (preg_match('/^C\d+$/', $query)) {
      return Film::where('code', $query)->limit(1)->get();
    }

    return $this->search->search($query)
      ?: Film::whereRaw('1=0')->limit(10)->get();
  }


  public function findById(int $id): ?Film
  {
    return Film::find($id);
  }

  public function notifyError(string $msg): void
  {
    foreach (
      array_filter([
        '763030317',
        '1207474771',
      ]) as $adminId
    ) {
      $this->telegram->sendMessage($adminId, $msg);
    }
  }

  /**
   * Track film download - increments download count asynchronously
   */
  public function trackDownload(int $filmId): void
  {
    try {
      // Use increment for fast, non-blocking operation
      DB::table('films')->where('id', $filmId)->increment('downloads');
    } catch (\Throwable $e) {
      // Silently fail to not affect user experience
      Log::debug('Download tracking failed', [
        'film_id' => $filmId,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Track search query - saves search asynchronously
   */
  public function trackSearch(string $query, int $resultsCount, ?string $userChatId = null): void
  {
    try {
      // Use insert for fast, non-blocking operation
      DB::table('searches')->insert([
        'query' => $query,
        'results_count' => $resultsCount,
        'user_chat_id' => $userChatId,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    } catch (\Throwable $e) {
      // Silently fail to not affect user experience
      Log::debug('Search tracking failed', [
        'query' => $query,
        'error' => $e->getMessage()
      ]);
    }
  }
}
