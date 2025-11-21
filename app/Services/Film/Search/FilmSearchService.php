<?php

namespace App\Services\Film\Search;

use App\Models\Film;
use App\Services\Translate\AITranslatorService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class FilmSearchService
{
  public function __construct(
    private readonly FilmSimilarityService    $similarity,
    private readonly AITranslatorService      $aiTranslator
  ) {}

  public function search(string $query): Collection
  {
    Log::info('🔍 Starting search', ['query' => $query]);

    $result = $this->tryOriginal($query);
    if ($result->isNotEmpty()) return $result;

    $result = $this->tryTransliteration($query);
    if ($result->isNotEmpty()) return $result;

    return $this->tryTranslation($query);
  }

  private function tryOriginal(string $query): Collection
  {
    $found = $this->find($query);

    if ($found->isNotEmpty()) {
      Log::info("✅ Found by original", ['count' => $found->count()]);
    }

    return $found;
  }

  private function tryTransliteration(string $query): Collection
  {
    $translit = $this->transliterate($query);

    if ($translit === $query) return collect();

    $found = $this->find($translit);

    if ($found->isNotEmpty()) {
      Log::info("✅ Found by transliteration", ['count' => $found->count()]);
    }

    return $found;
  }

  private function tryTranslation(string $query): Collection
  {
    $source = $this->detectLanguage($query);
    $targets = $this->languagesToTry($source);

    foreach ($targets as $lang) {
      try {
        $translated = $this->aiTranslator->translate($query, $source, $lang);

        if (!$translated || $translated === $query) continue;

        Log::info("🔄 Translated to {$lang}", ['text' => $translated]);

        $found = $this->find($translated);
        if ($found->isNotEmpty()) {
          Log::info("✅ Found by AI translation", ['count' => $found->count()]);
          return $found;
        }
      } catch (\Throwable $e) {
        Log::warning("⚠️ Translation {$lang} failed", ['error' => $e->getMessage()]);
      }
    }

    return collect();
  }

  /**
   * Database fuzzy search
   */
  private function find(string $title): Collection
  {
    $normalized = normalize_text($title);
    $words      = extract_words($normalized);
    $trigrams   = make_trigrams($normalized);

    $sql = $this->similarity->buildRelevanceSql($words, $trigrams);

    return Film::query()
      ->select('*')
      ->selectRaw("$sql AS relevance", [
        $normalized,
        "{$normalized}%",
        "%{$normalized}%",
        $normalized,
        mb_strlen($normalized)
      ])
      ->having('relevance', '>', 0)
      ->orderByDesc('relevance')
      ->orderByDesc('created_at')
      ->limit(20)
      ->get();
  }

  private function detectLanguage(string $text): string
  {
    // Russian (Cyrillic)
    if (preg_match('/[А-Яа-яЁёЎўҚқҒғҲҳ]/u', $text)) {
      return 'ru';
    }

    // English (har bir harf A–Z + max 1-2 so'z)
    if (preg_match('/[A-Za-z]/', $text) && !preg_match('/[İğıöüşç]/ui', $text)) {
      return 'en';
    }

    // Uzbek Latin
    return 'uz';
  }


  private function languagesToTry(string $src): array
  {
    return array_values(array_filter(
      ['uz', 'ru', 'en'],
      fn($l) => $l !== $src
    ));
  }

  private function transliterate(string $text): string
  {
    $map = [
      'А' => 'A',
      'Б' => 'B',
      'В' => 'V',
      'Г' => 'G',
      'Д' => 'D',
      'Е' => 'E',
      'Ё' => 'Yo',
      'Ж' => 'Zh',
      'З' => 'Z',
      'И' => 'I',
      'Й' => 'Y',
      'К' => 'K',
      'Л' => 'L',
      'М' => 'M',
      'Н' => 'N',
      'О' => 'O',
      'П' => 'P',
      'Р' => 'R',
      'С' => 'S',
      'Т' => 'T',
      'У' => 'U',
      'Ф' => 'F',
      'Х' => 'Kh',
      'Ц' => 'Ts',
      'Ч' => 'Ch',
      'Ш' => 'Sh',
      'Щ' => 'Shch',
      'Ы' => 'Y',
      'Э' => 'E',
      'Ю' => 'Yu',
      'Я' => 'Ya',
      'а' => 'a',
      'б' => 'b',
      'в' => 'v',
      'г' => 'g',
      'д' => 'd',
      'е' => 'e',
      'ё' => 'yo',
      'ж' => 'zh',
      'з' => 'z',
      'и' => 'i',
      'й' => 'y',
      'к' => 'k',
      'л' => 'l',
      'м' => 'm',
      'н' => 'n',
      'о' => 'o',
      'п' => 'p',
      'р' => 'r',
      'с' => 's',
      'т' => 't',
      'у' => 'u',
      'ф' => 'f',
      'х' => 'kh',
      'ц' => 'ts',
      'ч' => 'ch',
      'ш' => 'sh',
      'щ' => 'shch',
      'ы' => 'y',
      'э' => 'e',
      'ю' => 'yu',
      'я' => 'ya',
      'Ғ' => "G'",
      'Қ' => 'Q',
      'Ў' => "O'",
      'Ҳ' => 'H',
      'ғ' => "g'",
      'қ' => 'q',
      'ў' => "o'",
      'ҳ' => 'h',
    ];

    if (preg_match('/[А-Яа-яЁё]/u', $text)) return strtr($text, $map);
    return strtr($text, array_flip($map));
  }
}
