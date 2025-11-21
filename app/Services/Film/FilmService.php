<?php

namespace App\Services\Film;

use App\DTO\FilmData;
use App\Models\Film;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class FilmService
{
  public function store(FilmData $filmData): Film
  {
    $film = Film::updateOrCreate(
      [
        'message_id' => $filmData->messageId,
        'chat_id' => $filmData->chatId,
      ],
      $filmData->toArray()
    );

    Log::info('💾 Film saved successfully', [
      'id' => $film->id,
      'title' => $film->title,
      'details' => $film->details,
    ]);

    return $film;
  }

  public function search(string $query): Collection
  {
    return Film::searchByTitle($query)->limit(10)->get();
  }

  public function findById(int $id): ?Film
  {
    return Film::find($id);
  }
}
