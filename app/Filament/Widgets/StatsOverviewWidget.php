<?php

namespace App\Filament\Widgets;

use App\Models\Film;
use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayFilms = Film::whereDate('created_at', Carbon::today())->count();
        $todayStories = Story::whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Umumiy kinolar soni', Film::count())
                ->description('Botdagi barcha kinolar')
                ->descriptionIcon('heroicon-o-film')
                ->color('success'),

            Stat::make('Umumiy istoriyalar soni', Story::count())
                ->description('Botdagi barcha istoriyalar')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('info'),

            Stat::make('Bugungi joylangan kinolar', $todayFilms)
                ->description('Bugun qo\'shilgan kinolar soni')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),

            Stat::make('Bugungi joylangan istoriyalar', $todayStories)
                ->description('Bugun qo\'shilgan istoriyalar soni')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('danger'),
        ];
    }
}
