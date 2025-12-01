<?php

namespace App\Filament\Widgets;

use App\Models\Film;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalFilmsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Umumiy kinolar soni', Film::count())
                ->description('Botdagi barcha kinolar')
                ->descriptionIcon('heroicon-o-film')
                ->color('success'),
        ];
    }
}

