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

    $hasHighRelevance = $result->isNotEmpty() && $this->hasHighRelevance($result, $query);

    if ($hasHighRelevance) {
      Log::info("✅ Found by original query with high relevance", [
        'count' => $result->count(),
        'top_relevance' => $this->getRelevance($result->first())
      ]);
      return $result;
    }

    if ($result->isNotEmpty()) {
      Log::info("⚠️ Found results but relevance too low, trying other methods", [
        'count' => $result->count(),
        'top_relevance' => $this->getRelevance($result->first())
      ]);
    } else {
      Log::info("❌ No results with original query, trying transliteration...");
    }

    $result = $this->tryTransliteration($query);
    $hasHighRelevance = $result->isNotEmpty() && $this->hasHighRelevance($result, $query);

    if ($hasHighRelevance) {
      Log::info("✅ Found by transliteration with high relevance", [
        'count' => $result->count(),
        'top_relevance' => $this->getRelevance($result->first())
      ]);
      return $result;
    }

    if ($result->isNotEmpty()) {
      Log::info("⚠️ Found transliteration results but relevance too low, trying translation...");
    } else {
      Log::info("❌ No results with transliteration, trying translation...");
    }

    $result = $this->tryTranslation($query);
    if ($result->isNotEmpty()) {
      Log::info("✅ Found by translation", [
        'count' => $result->count(),
        'top_relevance' => $this->getRelevance($result->first())
      ]);
      return $result;
    }

    $originalResult = $this->tryOriginal($query);
    if ($originalResult->isNotEmpty() && !$this->hasHighRelevance($originalResult, $query)) {
      Log::info("⚠️ Translation failed, returning low-relevance original results as fallback", [
        'count' => $originalResult->count(),
        'top_relevance' => $this->getRelevance($originalResult->first())
      ]);
      return $originalResult;
    }

    Log::info("❌ No results found after all search methods");
    return new Collection();
  }

  private function tryOriginal(string $query): Collection
  {
    Log::info("🔍 Trying original query", ['query' => $query]);
    $found = $this->find($query);

    if ($found->isNotEmpty()) {
      Log::info("✅ Found by original", ['count' => $found->count()]);
    } else {
      Log::info("❌ No results with original query");
    }

    return $found;
  }

  private function tryTransliteration(string $query): Collection
  {
    Log::info("🔍 Trying transliteration", ['query' => $query]);
    $translit = $this->transliterate($query);

    if ($translit === $query) return new Collection();

    $found = $this->find($translit);

    return $found;
  }

  private function getRelevance($result): float
  {
    if (!$result) return 0;

    if (isset($result->relevance)) {
      return (float) $result->relevance;
    }

    if (isset($result->attributes['relevance'])) {
      return (float) $result->attributes['relevance'];
    }

    if (is_array($result) && isset($result['relevance'])) {
      return (float) $result['relevance'];
    }

    return 0;
  }

  private function hasHighRelevance(Collection $results, string $query): bool
  {
    if ($results->isEmpty())  return false;

    $topResult = $results->first();
    $relevance = $this->getRelevance($topResult);

    $threshold = 200;

    Log::info("🔍 Checking relevance", [
      'query' => $query,
      'top_title' => $topResult->title ?? '',
      'relevance' => $relevance,
      'threshold' => $threshold,
      'is_high' => $relevance >= $threshold
    ]);

    return $relevance >= $threshold;
  }

  private function tryTranslation(string $query): Collection
  {
    Log::info("🔄 Starting translation search", ['query' => $query]);

    $source = $this->detectLanguage($query);
    Log::info("🌐 Detected language: {$source}", ['query' => $query]);

    $targets = $this->languagesToTry($source);
    Log::info("🎯 Target languages to try", ['targets' => $targets, 'count' => count($targets)]);

    if (empty($targets)) {
      Log::warning("⚠️ No target languages to try");
      return new Collection();
    }

    foreach ($targets as $lang) {
      try {
        Log::info("🔄 Attempting translation", [
          'from' => $source,
          'to' => $lang,
          'query' => $query,
          'step' => 'starting'
        ]);

        $translated = $this->aiTranslator->translate($query, $source, $lang);

        Log::info("🔄 Translation response received", [
          'from' => $source,
          'to' => $lang,
          'translated' => $translated,
          'is_null' => is_null($translated)
        ]);

        if (!$translated || trim($translated) === '') {
          Log::warning("⚠️ Translation returned null or empty", [
            'from' => $source,
            'to' => $lang,
            'translated' => $translated
          ]);
          continue;
        }

        $normalizedOriginal = mb_strtolower(trim($query));
        $normalizedTranslated = mb_strtolower(trim($translated));

        if ($normalizedTranslated === $normalizedOriginal) {
          Log::info("⏭️ Translation same as original, skipping", [
            'translated' => $translated,
            'original' => $query
          ]);
          continue;
        }

        Log::info("✅ Translation successful", [
          'from' => $source,
          'to' => $lang,
          'original' => $query,
          'translated' => $translated
        ]);

        Log::info("🔍 Searching with translated text", ['translated' => $translated]);
        $found = $this->find($translated);

        if ($found->isNotEmpty()) {
          Log::info("✅ Found by AI translation", [
            'count' => $found->count(),
            'from' => $source,
            'to' => $lang,
            'translated' => $translated
          ]);
          return $found;
        }

        Log::info("❌ No results with direct translation, trying transliteration", [
          'translated' => $translated
        ]);

        $translitTranslated = $this->transliterate($translated);

        if ($translitTranslated !== $translated && $translitTranslated !== $query) {
          Log::info("🔄 Trying transliteration of translated text", [
            'translated' => $translated,
            'transliterated' => $translitTranslated
          ]);

          $found = $this->find($translitTranslated);
          if ($found->isNotEmpty()) {
            Log::info("✅ Found by transliterated translation", [
              'count' => $found->count(),
              'from' => $source,
              'to' => $lang,
              'translated' => $translated,
              'transliterated' => $translitTranslated
            ]);
            return $found;
          }
        } else {
          Log::info("⏭️ Transliteration same as translated or original, skipping");
        }

        Log::info("❌ No results found for this translation", [
          'translated' => $translated,
          'lang' => $lang
        ]);
      } catch (\Throwable $e) {
        Log::error("❌ Translation {$lang} failed with exception", [
          'error' => $e->getMessage(),
          'file' => $e->getFile(),
          'line' => $e->getLine(),
          'trace' => $e->getTraceAsString()
        ]);
      }
    }

    Log::info("❌ No results found after trying all translations");
    return new Collection();
  }

  private function find(string $title): Collection
  {
    $normalized = normalize_text($title);
    $words      = extract_words($normalized);
    $trigrams   = make_trigrams($normalized);

    $sql = $this->similarity->buildRelevanceSql($words, $trigrams);

    return Film::query()
      ->sourceType('TELEGRAM')
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
      ->limit(10)
      ->get();
  }

  private function detectLanguage(string $text): string
  {
    if (preg_match('/[А-Яа-яЁёЎўҚқҒғҲҳ]/u', $text)) {
      return 'ru';
    }

    if (preg_match('/[A-Za-z]/', $text) && !preg_match('/[İğıöüşç]/ui', $text)) {
      return 'en';
    }

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
