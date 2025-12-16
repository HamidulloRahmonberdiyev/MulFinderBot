<?php

namespace App\Filament\Resources\FilmResource\Pages;

use App\Filament\Resources\FilmResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFilm extends CreateRecord
{
    protected static string $resource = FilmResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

