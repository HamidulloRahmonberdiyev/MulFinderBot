<?php

namespace App\Services\Film;

use App\DTO\FilmData;
use App\Models\Film;
use App\Services\Film\Search\FilmSearchService;
use App\Services\TelegramService;
use Illuminate\Database\Eloquent\Collection;
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
      return Film::updateOrCreate(
        ['message_id' => $filmData->messageId, 'chat_id' => $filmData->chatId],
        $filmData->toArray()
      );
    } catch (\Throwable $e) {
      $this->notifyError("❌ Film saqlanmadi: {$filmData->title}\n{$e->getMessage()}");
      Log::error('Film store failed', ['error' => $e]);
      return null;
    }
  }

  public function search(string $query): Collection
  {
    if (preg_match('/^C\d+$/', $query)) {
      return Film::where('code', $query)->get();
    }

    return $this->search->search($query)
      ?: Film::whereRaw('1=0')->get();
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
}
