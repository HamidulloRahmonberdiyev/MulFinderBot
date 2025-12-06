<?php

namespace App\Filament\Widgets;

use App\Models\Search;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SearchesStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalSearches = Search::count();
        $todaySearches = Search::whereDate('created_at', Carbon::today())->count();
        $yesterdaySearches = Search::whereDate('created_at', Carbon::yesterday())->count();
        $weekSearches = Search::whereDate('created_at', '>=', Carbon::today()->subDays(7))->count();

        $change = $yesterdaySearches > 0
            ? round((($todaySearches - $yesterdaySearches) / $yesterdaySearches) * 100, 1)
            : 0;

        return [
            Stat::make('Umumiy qidiruvlar soni', number_format($totalSearches, 0, ',', ' '))
                ->description('Barcha vaqt davomida')
                ->descriptionIcon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->chart([$yesterdaySearches, $todaySearches]),

            Stat::make('Bugungi qidiruvlar', number_format($todaySearches, 0, ',', ' '))
                ->description($change != 0 ? ($change > 0 ? "+{$change}%" : "{$change}%") : 'O\'zgarmadi')
                ->descriptionIcon($change > 0 ? 'heroicon-o-arrow-trending-up' : ($change < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus'))
                ->color($change > 0 ? 'success' : ($change < 0 ? 'danger' : 'gray'))
                ->chart([$yesterdaySearches, $todaySearches]),

            Stat::make('Oxirgi 7 kunlik qidiruvlar', number_format($weekSearches, 0, ',', ' '))
                ->description('Haftalik statistika')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
        ];
    }
}
