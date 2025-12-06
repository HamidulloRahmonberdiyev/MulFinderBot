<?php

namespace App\Filament\Resources\SearchResource\Pages;

use App\Filament\Resources\SearchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSearches extends ListRecords
{
    protected static string $resource = SearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for searches - they are auto-generated
        ];
    }
}
