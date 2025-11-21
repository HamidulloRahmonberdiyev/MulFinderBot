<?php

namespace App\Handlers\Telegram;

use App\DTO\FilmData;
use App\Services\Film\FilmParserService;
use App\Services\Film\FilmService;
use Illuminate\Support\Facades\Log;

class ChannelPostHandler
{
  public function __construct(
    private readonly FilmParserService $parser,
    private readonly FilmService $filmService,
    private readonly string $channelId
  ) {}

  public function handle(array $post): void
  {
    try {
      if ($post['chat']['id'] != $this->channelId) {
        Log::info('⏭️ Post from different channel, skipping');
        return;
      }

      $caption = $post['caption'] ?? $post['text'] ?? null;

      if (!$caption) {
        Log::warning('⚠️ No caption or text found');
        return;
      }

      $parsed = $this->parser->parse($caption);

      if (!$parsed['title']) {
        $this->filmService->notifyError("❌ Xatolik! Film BOT ga saqlanmadi. Film nomini olish xatolik\nCaption:\n{$caption}");
        return;
      }

      $fileId = $post['video']['file_id']
        ?? $post['document']['file_id']
        ?? null;

      $filmData = new FilmData(
        title: $parsed['title'],
        details: $parsed['details'],
        messageId: $post['message_id'],
        chatId: $post['chat']['id'],
        fileId: $fileId
      );

      $this->filmService->store($filmData);
    } catch (\Exception $e) {
      $this->filmService->notifyError("❌ Film saqlashda xatolik: {$e->getMessage()}");
      Log::error('❌ Channel post handling failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }
}
