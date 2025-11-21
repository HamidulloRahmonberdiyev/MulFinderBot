<?php

namespace App\DTO;

class FilmData
{
  public function __construct(
    public readonly string $title,
    public readonly array $details,
    public readonly string $messageId,
    public readonly string $chatId,
    public readonly ?string $fileId = null
  ) {}

  public function toArray(): array
  {
    return [
      'title' => $this->title,
      'details' => $this->details,
      'message_id' => $this->messageId,
      'chat_id' => $this->chatId,
      'file_id' => $this->fileId,
    ];
  }
}
