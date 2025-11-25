<?php

namespace App\Filament\Resources\FilmResource\Pages;

use App\Filament\Resources\FilmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFilm extends EditRecord
{
    protected static string $resource = FilmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Filmini o\'chirish')
                ->modalDescription('Bu filmni o\'chirishni xohlaysizmi? Bu amalni qaytarib bo\'lmaydi.')
                ->modalSubmitActionLabel('Ha, o\'chirish')
                ->modalCancelActionLabel('Bekor qilish'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

