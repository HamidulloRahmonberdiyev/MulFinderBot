<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AITranslatorService
{
  private array $deepLxEndpoints = [
    'https://api.deeplx.org/translate',
    'https://api.deeplx.net/translate',
    'https://deeplx.vercel.app/translate',
  ];

  private array $libreTranslateEndpoints = [
    'https://translate.terraprint.co/translate',
    'https://libretranslate.de/translate',
    'https://translate.argosopentech.com/translate',
  ];

  /**
   * Main translate flow
   */
  public function translate(string $text, string $from, string $to): ?string
  {
    // Short text – skip
    if (mb_strlen($text) < 2) {
      return $text;
    }

    // 1) DeepLX (AI)
    foreach ($this->deepLxEndpoints as $url) {
      $translated = $this->deepLX($url, $text, $from, $to);
      if ($this->isValidTranslation($text, $translated)) {
        return $translated;
      }
    }

    // 2) LibreTranslate (AI)
    foreach ($this->libreTranslateEndpoints as $url) {
      $translated = $this->libreTranslate($url, $text, $from, $to);
      if ($this->isValidTranslation($text, $translated)) {
        return $translated;
      }
    }

    // 3) MyMemory (fallback)
    $translated = $this->myMemory($text, $from, $to);
    if ($this->isValidTranslation($text, $translated)) {
      return $translated;
    }

    // 4) Google Translate mirror (VERY ACCURATE)
    $translated = $this->googleTranslate($text, $from, $to);
    if ($this->isValidTranslation($text, $translated)) {
      return $translated;
    }

    return null;
  }

  /**
   * Check translation validity
   */
  private function isValidTranslation(string $original, ?string $translated): bool
  {
    if (!$translated) return false;

    // Same as original → not valid
    if (trim(mb_strtolower($translated)) === trim(mb_strtolower($original))) {
      return false;
    }

    // URL → invalid
    if (filter_var($translated, FILTER_VALIDATE_URL)) {
      return false;
    }

    return true;
  }

  /**
   * 1) DeepLX
   */
  private function deepLX(string $url, string $text, string $from, string $to): ?string
  {
    try {
      $response = Http::timeout(4)->post($url, [
        'text' => $text,
        'source_lang' => strtoupper($from),
        'target_lang' => strtoupper($to),
      ]);

      return $response['data'] ?? null;
    } catch (\Exception $e) {
      Log::warning("DeepLX fail @ $url", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * 2) LibreTranslate
   */
  private function libreTranslate(string $url, string $text, string $from, string $to): ?string
  {
    try {
      $response = Http::timeout(4)->post($url, [
        'q'       => $text,
        'source'  => $from,
        'target'  => $to,
        'format'  => 'text'
      ]);

      return $response['translatedText'] ?? null;
    } catch (\Exception $e) {
      Log::warning("LibreTranslate fail @ $url", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * 3) MyMemory
   */
  private function myMemory(string $text, string $from, string $to): ?string
  {
    try {
      $response = Http::timeout(5)->get(
        "https://api.mymemory.translated.net/get",
        [
          'q' => $text,
          'langpair' => "{$from}|{$to}"
        ]
      );

      return $response['responseData']['translatedText'] ?? null;
    } catch (\Exception $e) {
      Log::warning("MyMemory fail", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * 4) Google Translate unofficial (VERY accurate for movie titles)
   */
  private function googleTranslate(string $text, string $from, string $to): ?string
  {
    try {
      $response = Http::timeout(5)->get('https://translate.googleapis.com/translate_a/single', [
        'client' => 'gtx',
        'dt' => 't',
        'sl' => $from,
        'tl' => $to,
        'q' => $text,
      ]);

      if (!$response->successful()) return null;

      $json = $response->json();

      return $json[0][0][0] ?? null;
    } catch (\Exception $e) {
      Log::warning("Google Translate fail", ['error' => $e->getMessage()]);
      return null;
    }
  }
}
