<?php

namespace App\Handlers\Telegram;

use App\Services\Film\FilmService;
use App\Services\Message\MessageFormatterService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallbackQueryHandler
{
  public function __construct(
    private readonly TelegramService $telegram,
    private readonly FilmService $filmService,
    private readonly MessageFormatterService $formatter
  ) {}

  public function handle(array $callbackQuery): void
  {
    try {
      $chatId = $callbackQuery['message']['chat']['id'];
      $messageId = $callbackQuery['message']['message_id'];
      $data = $callbackQuery['data'];
      $callbackId = $callbackQuery['id'];

      Log::info('🔘 Processing callback query', ['data' => $data]);

      if (!str_starts_with($data, 'film_')) {
        return;
      }

      $filmId = (int) str_replace('film_', '', $data);
      $film = $this->filmService->findById($filmId);

      if (!$film) {
        $this->telegram->answerCallbackQuery(
          $callbackId,
          "❌ Film topilmadi",
          true
        );
        return;
      }

      $this->telegram->answerCallbackQuery(
        $callbackId,
        "✅ {$film->title} yuklanmoqda..."
      );

      $this->markButtonAsSelected($chatId, $messageId, $filmId);

      $this->sendFilm($chatId, $film);
    } catch (\Exception $e) {
      Log::error('❌ Callback query handling failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }

  private function markButtonAsSelected(int|string $chatId, int|string $messageId, int $filmId): void
  {
    try {
      $response = Http::get("https://api.telegram.org/bot{$this->telegram->token}/getMessage", [
        'chat_id' => $chatId,
        'message_id' => $messageId
      ]);

      if (!$response->successful()) {
        return;
      }

      $message = $response->json();
      $keyboard = $message['result']['reply_markup']['inline_keyboard'] ?? [];

      foreach ($keyboard as &$row) {
        foreach ($row as &$button) {
          if ($button['callback_data'] === "film_{$filmId}") {
            $button['text'] = '✅ ' . str_replace('▶️ ', '', $button['text']);
          }
        }
      }

      $this->telegram->editMessageReplyMarkup(
        $chatId,
        $messageId,
        ['inline_keyboard' => $keyboard]
      );
    } catch (\Exception $e) {
      Log::debug('Button update failed', ['error' => $e->getMessage()]);
    }
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

    Log::info('✅ Film sent from callback', [
      'film_id' => $film->id,
      'chat_id' => $chatId
    ]);
  }
}
