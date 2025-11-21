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
    Log::info("🔄 AITranslatorService: Starting translation", [
      'text' => $text,
      'from' => $from,
      'to' => $to,
      'text_length' => mb_strlen($text)
    ]);

    // Short text – skip
    if (mb_strlen($text) < 2) {
      Log::info("⏭️ Text too short, skipping translation");
      return $text;
    }

    // Check if Uzbek is involved - only use services that support it
    $hasUzbek = ($from === 'uz' || $to === 'uz');
    
    if (!$hasUzbek) {
      // 1) DeepLX (AI) - doesn't support Uzbek
      Log::info("🔄 Trying DeepLX endpoints", ['count' => count($this->deepLxEndpoints)]);
      foreach ($this->deepLxEndpoints as $url) {
        $translated = $this->deepLX($url, $text, $from, $to);
        Log::info("🔄 DeepLX response", ['url' => $url, 'translated' => $translated, 'is_valid' => $this->isValidTranslation($text, $translated)]);
        if ($this->isValidTranslation($text, $translated)) {
          Log::info("✅ Translation successful via DeepLX", ['translated' => $translated]);
          return $translated;
        }
      }

      // 2) LibreTranslate (AI) - doesn't support Uzbek
      Log::info("🔄 Trying LibreTranslate endpoints", ['count' => count($this->libreTranslateEndpoints)]);
      foreach ($this->libreTranslateEndpoints as $url) {
        $translated = $this->libreTranslate($url, $text, $from, $to);
        Log::info("🔄 LibreTranslate response", ['url' => $url, 'translated' => $translated, 'is_valid' => $this->isValidTranslation($text, $translated)]);
        if ($this->isValidTranslation($text, $translated)) {
          Log::info("✅ Translation successful via LibreTranslate", ['translated' => $translated]);
          return $translated;
        }
      }
    }

    // 3) Google Translate mirror (VERY ACCURATE, supports Uzbek)
    Log::info("🔄 Trying Google Translate", ['supports_uzbek' => true]);
    $translated = $this->googleTranslate($text, $from, $to);
    Log::info("🔄 Google Translate response", ['translated' => $translated, 'is_valid' => $this->isValidTranslation($text, $translated)]);
    if ($this->isValidTranslation($text, $translated)) {
      Log::info("✅ Translation successful via Google Translate", ['translated' => $translated]);
      return $translated;
    }

    // 4) MyMemory (supports Uzbek)
    Log::info("🔄 Trying MyMemory", ['supports_uzbek' => true]);
    $translated = $this->myMemory($text, $from, $to);
    Log::info("🔄 MyMemory response", ['translated' => $translated, 'is_valid' => $this->isValidTranslation($text, $translated)]);
    if ($this->isValidTranslation($text, $translated)) {
      Log::info("✅ Translation successful via MyMemory", ['translated' => $translated]);
      return $translated;
    }

    Log::warning("❌ All translation services failed", [
      'text' => $text,
      'from' => $from,
      'to' => $to
    ]);

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
   * Normalize language code for DeepLX
   */
  private function normalizeDeepLXLang(string $lang): string
  {
    return match($lang) {
      'ru' => 'RU',
      'en' => 'EN',
      default => strtoupper($lang)
    };
  }

  /**
   * 1) DeepLX
   */
  private function deepLX(string $url, string $text, string $from, string $to): ?string
  {
    try {
      $fromLang = $this->normalizeDeepLXLang($from);
      $toLang = $this->normalizeDeepLXLang($to);
      
      Log::info("🔄 DeepLX request", [
        'url' => $url,
        'text' => $text,
        'from' => $from,
        'to' => $to,
        'from_normalized' => $fromLang,
        'to_normalized' => $toLang
      ]);

      $response = Http::timeout(4)->post($url, [
        'text' => $text,
        'source_lang' => $fromLang,
        'target_lang' => $toLang,
      ]);

      $translated = $response['data'] ?? null;
      Log::info("🔄 DeepLX response", ['translated' => $translated, 'success' => $response->successful()]);
      
      return $translated;
    } catch (\Exception $e) {
      Log::warning("DeepLX fail @ $url", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Normalize language code for LibreTranslate
   */
  private function normalizeLibreLang(string $lang): string
  {
    return match($lang) {
      'ru' => 'ru',
      'en' => 'en',
      default => strtolower($lang)
    };
  }

  /**
   * 2) LibreTranslate
   */
  private function libreTranslate(string $url, string $text, string $from, string $to): ?string
  {
    try {
      $fromLang = $this->normalizeLibreLang($from);
      $toLang = $this->normalizeLibreLang($to);
      
      Log::info("🔄 LibreTranslate request", [
        'url' => $url,
        'text' => $text,
        'from' => $from,
        'to' => $to,
        'from_normalized' => $fromLang,
        'to_normalized' => $toLang
      ]);

      $response = Http::timeout(4)->post($url, [
        'q'       => $text,
        'source'  => $fromLang,
        'target'  => $toLang,
        'format'  => 'text'
      ]);

      $translated = $response['translatedText'] ?? null;
      Log::info("🔄 LibreTranslate response", ['translated' => $translated, 'success' => $response->successful()]);
      
      return $translated;
    } catch (\Exception $e) {
      Log::warning("LibreTranslate fail @ $url", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Normalize language code for MyMemory
   */
  private function normalizeMyMemoryLang(string $lang): string
  {
    return match($lang) {
      'uz' => 'uz', // MyMemory supports Uzbek
      'ru' => 'ru',
      'en' => 'en',
      default => strtolower($lang)
    };
  }

  /**
   * 3) MyMemory
   */
  private function myMemory(string $text, string $from, string $to): ?string
  {
    try {
      $fromLang = $this->normalizeMyMemoryLang($from);
      $toLang = $this->normalizeMyMemoryLang($to);
      
      Log::info("🔄 MyMemory request", [
        'text' => $text,
        'from' => $from,
        'to' => $to,
        'from_normalized' => $fromLang,
        'to_normalized' => $toLang,
        'langpair' => "{$fromLang}|{$toLang}"
      ]);

      $response = Http::timeout(5)->get(
        "https://api.mymemory.translated.net/get",
        [
          'q' => $text,
          'langpair' => "{$fromLang}|{$toLang}"
        ]
      );

      $translated = $response['responseData']['translatedText'] ?? null;
      Log::info("🔄 MyMemory response", ['translated' => $translated, 'success' => $response->successful()]);
      
      return $translated;
    } catch (\Exception $e) {
      Log::warning("MyMemory fail", ['error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Normalize language code for Google Translate
   */
  private function normalizeGoogleLang(string $lang): string
  {
    return match($lang) {
      'uz' => 'uz', // Google Translate supports Uzbek
      'ru' => 'ru',
      'en' => 'en',
      default => strtolower($lang)
    };
  }

  /**
   * 4) Google Translate unofficial (VERY accurate for movie titles)
   */
  private function googleTranslate(string $text, string $from, string $to): ?string
  {
    try {
      $fromLang = $this->normalizeGoogleLang($from);
      $toLang = $this->normalizeGoogleLang($to);
      
      Log::info("🔄 Google Translate request", [
        'text' => $text,
        'from' => $from,
        'to' => $to,
        'from_normalized' => $fromLang,
        'to_normalized' => $toLang
      ]);

      $response = Http::timeout(5)->get('https://translate.googleapis.com/translate_a/single', [
        'client' => 'gtx',
        'dt' => 't',
        'sl' => $fromLang,
        'tl' => $toLang,
        'q' => $text,
      ]);

      if (!$response->successful()) {
        Log::warning("❌ Google Translate request failed", ['status' => $response->status()]);
        return null;
      }

      $json = $response->json();
      $translated = $json[0][0][0] ?? null;
      
      Log::info("🔄 Google Translate response", ['translated' => $translated]);
      
      return $translated;
    } catch (\Exception $e) {
      Log::warning("Google Translate fail", ['error' => $e->getMessage()]);
      return null;
    }
  }
}
