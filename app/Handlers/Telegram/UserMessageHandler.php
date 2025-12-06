<?php

namespace App\Handlers\Telegram;

use App\Services\Film\FilmService;
use App\Services\Message\MessageFormatterService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class UserMessageHandler
{
  public function __construct(
    private readonly TelegramService $telegram,
    private readonly FilmService $filmService,
    private readonly MessageFormatterService $formatter
  ) {}

  public function handle(array $message): void
  {
    try {
      $chatId = $message['chat']['id'];
      $text = $message['text'] ?? '';

      match ($text) {
        '/start' => $this->handleStart($chatId),
        '🎬 Multfilmlarni Topish' => $this->handleSearchButton($chatId),
        default => $this->handleSearch($chatId, $text)
      };
    } catch (\Exception $e) {
      Log::error('❌ User message handling failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      $this->telegram->sendMessage(
        $message['chat']['id'],
        "❌ Xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring."
      );
    }
  }

  private function handleStart(int|string $chatId): void
  {
    $this->telegram->sendMessage(
      $chatId,
      $this->formatter->welcome(),
      $this->formatter->mainKeyboard()
    );
  }

  private function handleSearchButton(int|string $chatId): void
  {
    $this->telegram->sendMessage(
      $chatId,
      $this->formatter->searchInstruction()
    );
  }

  private function handleSearch(int|string $chatId, string $query): void
  {
    if (strlen($query) < 2) {
      $this->telegram->sendMessage(
        $chatId,
        $this->formatter->validationError("Iltimos, kamida 2 ta harf kiriting.")
      );
      return;
    }

    $this->telegram->sendChatAction($chatId, 'typing');

    $films = $this->filmService->search($query);

    // Track search - non-blocking
    $this->filmService->trackSearch($query, $films->count(), (string) $chatId);

    if ($films->isEmpty()) {
      $this->telegram->sendMessage(
        $chatId,
        $this->formatter->notFound($query)
      );
      return;
    }

    if ($films->count() === 1) {
      $this->sendFilm($chatId, $films->first());
      return;
    }

    $this->sendFilmList($chatId, $films, $query);
  }

  private function sendFilmList(int|string $chatId, $films, string $query): void
  {
    $message = $this->formatter->filmListHeader($query, $films->count());

    foreach ($films as $index => $film) {
      $message .= $this->formatter->filmListItem($index + 1, $film) . "\n";
    }

    $message .= "\n👇 <b>Kerakli filmni tanlang:</b>";

    $this->telegram->sendMessage(
      $chatId,
      $message,
      $this->formatter->filmListKeyboard($films)
    );
  }

  private function sendFilm(int|string $chatId, $film): void
  {
    $this->telegram->sendMessage(
      $chatId,
      $this->formatter->filmDetails($film)
    );

    $this->telegram->sendChatAction($chatId, 'upload_video');

    sleep(1);
    $this->telegram->copyMessage($chatId, $film->chat_id, $film->message_id);

    // Track download - non-blocking
    $this->filmService->trackDownload($film->id);

    Log::info('✅ Film sent successfully', [
      'film_id' => $film->id,
      'title' => $film->title,
      'chat_id' => $chatId
    ]);
  }
}
