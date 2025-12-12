<?php

namespace App\Filament\Resources\FilmResource\Pages;

use App\Filament\Resources\FilmResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use App\Services\TelegramService;
use Illuminate\Support\HtmlString;

class ViewFilm extends ViewRecord
{
    protected static string $resource = FilmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openTelegram')
                ->label('Telegramda ochish')
                ->icon(fn() => new HtmlString('
        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0 0 48 48">
<path fill="#29b6f6" d="M24 4A20 20 0 1 0 24 44A20 20 0 1 0 24 4Z"></path><path fill="#fff" d="M33.95,15l-3.746,19.126c0,0-0.161,0.874-1.245,0.874c-0.576,0-0.873-0.274-0.873-0.274l-8.114-6.733 l-3.97-2.001l-5.095-1.355c0,0-0.907-0.262-0.907-1.012c0-0.625,0.933-0.923,0.933-0.923l21.316-8.468 c-0.001-0.001,0.651-0.235,1.126-0.234C33.667,14,34,14.125,34,14.5C34,14.75,33.95,15,33.95,15z"></path><path fill="#b0bec5" d="M23,30.505l-3.426,3.374c0,0-0.149,0.115-0.348,0.12c-0.069,0.002-0.143-0.009-0.219-0.043 l0.964-5.965L23,30.505z"></path><path fill="#cfd8dc" d="M29.897,18.196c-0.169-0.22-0.481-0.26-0.701-0.093L16,26c0,0,2.106,5.892,2.427,6.912 c0.322,1.021,0.58,1.045,0.58,1.045l0.964-5.965l9.832-9.096C30.023,18.729,30.064,18.416,29.897,18.196z"></path>
</svg>
    '))
                ->extraAttributes([
                    'class' => '
            flex items-center gap-2 px-3 py-2 rounded-lg 
            border border-[#229ED9] text-[#229ED9]
            hover:bg-[#229ED910] 
            transition
        ',
                    'style' => 'background: transparent;'
                ])
                ->color('gray')
                ->action(function () {
                    $film = $this->record;

                    if (!$film->chat_id || !$film->message_id) {
                        Notification::make()
                            ->danger()
                            ->title('Xatolik')
                            ->body('Film uchun chat_id yoki message_id mavjud emas.')
                            ->send();
                        return;
                    }

                    $chatId = ltrim($film->chat_id, '-');
                    $url = "https://t.me/cartoons_db/{$film->message_id}";

                    return redirect()->away($url);
                }),
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Filmini o\'chirish')
                ->modalDescription('Bu filmni o\'chirishni xohlaysizmi? Bu amalni qaytarib bo\'lmaydi.')
                ->modalSubmitActionLabel('Ha, o\'chirish')
                ->modalCancelActionLabel('Bekor qilish'),
        ];
    }
}
