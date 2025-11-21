<?php

namespace App\Services\Film\Search;

class FilmSimilarityService
{
  public function buildRelevanceSql(array $words, array $trigrams): string
  {
    return "
            (
                /* EXACT MATCH */
                CASE WHEN LOWER(title) = LOWER(?) THEN 300 ELSE 0 END +

                /* STARTS WITH */
                CASE WHEN LOWER(title) LIKE LOWER(?) THEN 200 ELSE 0 END +

                /* LIKE */
                CASE WHEN LOWER(title) LIKE LOWER(?) THEN 150 ELSE 0 END +

                /* COMMON WORDS */
                (" . $this->commonWordsSql($words) . ") * 30 +

                /* TRIGRAMS */
                (" . $this->trigramSql($trigrams) . ") * 15 +

                /* SOUNDEX */
                CASE WHEN SOUNDEX(title) = SOUNDEX(?) THEN 20 ELSE 0 END +

                /* LENGTH SIMILARITY */
                (100 - ABS(LENGTH(title) - ?))
            )
        ";
  }

  private function commonWordsSql(array $words): string
  {
    if (empty($words)) return "0";

    return implode(' + ', array_map(
      fn($w) =>
      "CASE WHEN LOWER(title) LIKE LOWER('%{$w}%') THEN 1 ELSE 0 END",
      array_map('addslashes', $words)
    ));
  }

  private function trigramSql(array $trigrams): string
  {
    if (empty($trigrams)) return "0";

    return implode(' + ', array_map(
      fn($tg) =>
      "CASE WHEN LOWER(title) LIKE LOWER('%{$tg}%') THEN 1 ELSE 0 END",
      array_map('addslashes', $trigrams)
    ));
  }
}
