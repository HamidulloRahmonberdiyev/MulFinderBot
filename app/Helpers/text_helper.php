<?php

if (!function_exists('normalize_text')) {
  function normalize_text(string $text): string
  {
    $text = mb_strtolower($text);
    $text = preg_replace('/[^a-zA-Z0-9А-Яа-яЁёЎўҚқҒғҲҳ\s]/u', ' ', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
  }
}

if (!function_exists('extract_words')) {
  function extract_words(string $text): array
  {
    return array_filter(explode(' ', $text));
  }
}

if (!function_exists('make_trigrams')) {
  function make_trigrams(string $text): array
  {
    $text = str_replace(' ', '', $text);
    $result = [];

    for ($i = 0; $i < mb_strlen($text) - 2; $i++) {
      $result[] = mb_substr($text, $i, 3);
    }

    return $result;
  }
}
