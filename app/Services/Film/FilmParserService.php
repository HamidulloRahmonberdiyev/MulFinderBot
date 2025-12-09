<?php

namespace App\Services\Film;

use Illuminate\Support\Facades\Log;

class FilmParserService
{
  public function parse(string $caption): array
  {
    $cleanCaption = $this->removeEmojis($caption);

    $lines = $this->splitIntoLines($cleanCaption);

    $title = $this->extractTitle($lines);
    $details = $this->extractDetails($lines);

    $result = [
      'title' => $title,
      'details' => $details
    ];

    Log::info('✅ Parsing completed', $result);

    return $result;
  }

  private function removeEmojis(string $text): string
  {
    $patterns = [
      '/[\x{1F600}-\x{1F64F}]/u', // Emoticons
      '/[\x{1F300}-\x{1F5FF}]/u', // Symbols & Pictographs
      '/[\x{1F680}-\x{1F6FF}]/u', // Transport & Map
      '/[\x{1F700}-\x{1F77F}]/u', // Alchemical
      '/[\x{1F780}-\x{1F7FF}]/u', // Geometric Shapes
      '/[\x{1F800}-\x{1F8FF}]/u', // Supplemental Arrows
      '/[\x{1F900}-\x{1F9FF}]/u', // Supplemental Symbols
      '/[\x{1FA00}-\x{1FA6F}]/u', // Chess Symbols
      '/[\x{1FA70}-\x{1FAFF}]/u', // Symbols Extended
      '/[\x{2600}-\x{26FF}]/u',   // Miscellaneous Symbols
      '/[\x{2700}-\x{27BF}]/u',   // Dingbats
      '/[\x{FE00}-\x{FE0F}]/u',   // Variation Selectors
      '/[\x{1F1E6}-\x{1F1FF}]/u', // Flags
    ];

    return preg_replace($patterns, '', $text);
  }

  private function splitIntoLines(string $text): array
  {
    $lines = explode("\n", $text);
    $lines = array_map('trim', $lines);
    return array_filter($lines, fn($line) => !empty($line));
  }

  private function extractTitle(array $lines): ?string
  {
    if (empty($lines)) return null;

    $firstLine = reset($lines);

    // 1. Avval hashtag bormi tekshirish
    if (preg_match('/#([^\s#\n]+)/', $firstLine, $matches)) {
      $hashtag = $matches[1];
      $title = str_replace('_', ' ', $hashtag);
      $title = preg_replace('/\(\d{4}\)/', '', $title);
      $title = trim($title);

      Log::info('🏷️ Title extracted from hashtag', [
        'hashtag' => $hashtag,
        'title' => $title
      ]);

      return $title;
    }

    // 2. Hashtag bo'lmasa, butun lines massividan quote qidirish
    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line)) continue;

      $quoteLine = preg_replace('/^>\s*/', '', $line);
      $quoteLine = preg_replace('/^[▸▹►▶]\s*/', '', $quoteLine);

      $quotePatterns = [
        '/^[\x{201C}\x{201D}"](.+?)[\x{201C}\x{201D}"]$/u',  // "text" yoki "text"
        '/^[\x{2018}\x{2019}\'](.+?)[\x{2018}\x{2019}\']$/u', // 'text' yoki 'text'
        '/^\x{00AB}(.+?)\x{00BB}$/u',                         // «text»
        '/^"(.+?)"$/',                                         // "text"
        '/^\'(.+?)\'$/',                                       // 'text'
        '/^(.+)$/',                                            // Agar quote belgisi bo'lmasa, butun qatorni ol
      ];

      foreach ($quotePatterns as $pattern) {
        if (preg_match($pattern, $quoteLine, $matches)) {
          $title = trim($matches[1]);

          if (!empty($title) && strlen($title) > 3) {
            Log::info('🏷️ Title extracted from quote', [
              'original_line' => $line,
              'quote' => $matches[0],
              'title' => $title
            ]);

            return $title;
          }
        }
      }
    }

    foreach ($lines as $line) {
      $cleaned = trim($line);
      $cleaned = preg_replace('/^[>#▸▹►▶\s]+/', '', $cleaned);

      if (!empty($cleaned) && strlen($cleaned) > 3) {
        Log::info('🏷️ Title extracted from first non-empty line', [
          'title' => $cleaned
        ]);

        return $cleaned;
      }
    }

    return null;
  }

  private function extractDetails(array $lines): array
  {
    $details = [];

    foreach ($lines as $line) {
      if (str_starts_with($line, '#')) continue;

      if (str_contains($line, ':')) {
        [$key, $value] = array_map('trim', explode(':', $line, 2));

        if (!empty($key) && !empty($value)) {
          $details[$key] = $value;
          Log::info('📌 Detail extracted', [
            'key' => $key,
            'value' => $value
          ]);
        }
      }
    }

    return $details;
  }
}
