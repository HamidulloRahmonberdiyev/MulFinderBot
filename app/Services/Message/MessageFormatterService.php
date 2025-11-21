<?php

namespace App\Services\Message;

use App\Models\Film;
use Illuminate\Support\Collection;

class MessageFormatterService
{
  public function welcome(): string
  {
    return <<<HTML
         <b>Assalomu alaykum!

        Men sizga kerakli multfilmlarni topib beraman.

        🔍 Qanday foydalanish:
        1️⃣ Pastdagi tugmani bosing
        2️⃣ Film nomini yozing

        👇 Boshlash uchun tugmani bosing</b>
        HTML;
  }

  public function searchInstruction(): string
  {
    return "🔍 Multfilm nomini yozing, men sizga topib beraman!\n\n💡 Masalan: <code>Kung Fu Panda</code>";
  }

  public function notFound(string $query): string
  {
    return "😔 <b>Afsuski '{$query}'</b> nomi bilan multfilm topilmadi.\n\n <em>Boshqa nom bilan urinib ko'ring.</em>";
  }

  public function validationError(string $message): string
  {
    return "⚠️ {$message}";
  }

  public function filmListHeader(string $query, int $count): string
  {
    return <<<HTML
        🔎 <b>Qidiruv natijalari:</b> '{$query}'
        ━━━━━━━━━━━━━━━━━━━━
        📊 Topildi: <b>{$count} ta natija</b>


        HTML;
  }

  public function filmListItem(int $index, Film $film): string
  {
    $shortDetails = $film->getShortDetails();
    return "{$index}. 🎥 <b>{$film->title}</b>{$shortDetails}";
  }

  public function filmDetails(Film $film): string
  {
    $details = $film->getFormattedDetails();

    return <<<HTML
        ━━━━━━━━━━━━━━━━━━━━
        🎬 <b>{$film->title}</b>
        ━━━━━━━━━━━━━━━━━━━━

        {$details}

        📥 <i>Film yuklanmoqda...</i>
        HTML;
  }

  public function mainKeyboard(): array
  {
    return [
      'keyboard' => [
        [['text' => '🎬 Multfilmlarni Topish']]
      ],
      'resize_keyboard' => true,
      'one_time_keyboard' => false,
    ];
  }

  public function filmListKeyboard(Collection $films): array
  {
    $buttons = [];
    $row = [];

    $i = 1;

    foreach ($films as $film) {

      $row[] = [
        'text' => (string)$i,
        'callback_data' => "film_{$film->id}"
      ];

      if (count($row) === 5) {
        $buttons[] = $row;
        $row = [];
      }

      $i++;
    }

    if (!empty($row)) $buttons[] = $row;

    return [
      'inline_keyboard' => $buttons
    ];
  }
}
