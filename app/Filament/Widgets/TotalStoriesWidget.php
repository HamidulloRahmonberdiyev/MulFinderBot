<?php

namespace App\Filament\Widgets;

use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalStoriesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Umumiy istoriyalar soni', Story::count())
                ->description('Botdagi barcha istoriyalar')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('info'),
        ];
    }
}

