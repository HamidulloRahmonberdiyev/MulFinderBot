<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
  private string $apiUrl;

  public function __construct(
    public readonly string $token
  ) {
    $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
  }

  public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
  {
    try {
      $params = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
      ];

      if ($replyMarkup) {
        $params['reply_markup'] = json_encode($replyMarkup);
      }

      $response = Http::post("{$this->apiUrl}/sendMessage", $params);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to send message', [
        'error' => $e->getMessage(),
        'chat_id' => $chatId
      ]);
      return false;
    }
  }

  public function forwardMessage(int|string $toChatId, int|string $fromChatId, int|string $messageId): bool
  {
    try {
      $response = Http::post("{$this->apiUrl}/forwardMessage", [
        'chat_id' => $toChatId,
        'from_chat_id' => $fromChatId,
        'message_id' => $messageId,
      ]);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to forward message', [
        'error' => $e->getMessage(),
        'to_chat_id' => $toChatId,
        'message_id' => $messageId
      ]);
      return false;
    }
  }

  public function sendChatAction(int|string $chatId, string $action): void
  {
    try {
      Http::post("{$this->apiUrl}/sendChatAction", [
        'chat_id' => $chatId,
        'action' => $action,
      ]);
    } catch (\Exception $e) {
    }
  }


  public function answerCallbackQuery(string $callbackId, string $text, bool $showAlert = false): bool
  {
    try {
      $response = Http::post("{$this->apiUrl}/answerCallbackQuery", [
        'callback_query_id' => $callbackId,
        'text' => $text,
        'show_alert' => $showAlert,
      ]);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to answer callback query', [
        'error' => $e->getMessage(),
        'callback_id' => $callbackId
      ]);
      return false;
    }
  }

  public function editMessageReplyMarkup(int|string $chatId, int|string $messageId, array $replyMarkup): bool
  {
    try {
      $response = Http::post("{$this->apiUrl}/editMessageReplyMarkup", [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'reply_markup' => json_encode($replyMarkup),
      ]);

      return $response->successful();
    } catch (\Exception $e) {
      return false;
    }
  }

  public function copyMessage(int|string $toChatId, int|string $fromChatId, int|string $messageId): bool
  {
    try {
      $response = Http::post("{$this->apiUrl}/copyMessage", [
        'chat_id' => $toChatId,
        'from_chat_id' => $fromChatId,
        'message_id' => $messageId,
      ]);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to copy message', [
        'error' => $e->getMessage(),
        'to_chat_id' => $toChatId,
        'message_id' => $messageId
      ]);
      return false;
    }
  }

  public function sendVideo(int|string $chatId, string $fileId, ?string $caption = null): bool
  {
    try {
      $params = [
        'chat_id' => $chatId,
        'video' => $fileId,
      ];

      if ($caption) {
        $params['caption'] = $caption;
        $params['parse_mode'] = 'HTML';
      }

      $response = Http::post("{$this->apiUrl}/sendVideo", $params);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to send video', [
        'error' => $e->getMessage(),
        'chat_id' => $chatId
      ]);
      return false;
    }
  }

  public function sendDocument(int|string $chatId, string $fileId, ?string $caption = null): bool
  {
    try {
      $params = [
        'chat_id' => $chatId,
        'document' => $fileId,
      ];

      if ($caption) {
        $params['caption'] = $caption;
        $params['parse_mode'] = 'HTML';
      }

      $response = Http::post("{$this->apiUrl}/sendDocument", $params);

      return $response->successful();
    } catch (\Exception $e) {
      Log::error('Failed to send document', [
        'error' => $e->getMessage(),
        'chat_id' => $chatId
      ]);
      return false;
    }
  }

  public function setWebhook(string $url, array $allowedUpdates = []): array
  {
    $response = Http::post("{$this->apiUrl}/setWebhook", [
      'url' => $url,
      'allowed_updates' => $allowedUpdates,
    ]);

    return $response->json();
  }
}
