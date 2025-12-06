<?php

namespace App\Filament\Widgets;

use App\Models\Film;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DownloadsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalDownloads = Film::sum('downloads');

        // Bugungi yuklab olinishlar (taxminiy)
        $todayDownloads = DB::table('films')
            ->whereDate('updated_at', Carbon::today())
            ->sum('downloads');

        // Kechagi yuklab olinishlar (taxminiy)
        $yesterdayDownloads = DB::table('films')
            ->whereDate('updated_at', Carbon::yesterday())
            ->sum('downloads');

        // Oxirgi 7 kunlik yuklab olinishlar (taxminiy)
        $weekDownloads = DB::table('films')
            ->whereDate('updated_at', '>=', Carbon::today()->subDays(7))
            ->sum('downloads');

        $change = $yesterdayDownloads > 0
            ? round((($todayDownloads - $yesterdayDownloads) / $yesterdayDownloads) * 100, 1)
            : 0;

        return [
            Stat::make('Umumiy yuklab olinishlar', number_format($totalDownloads, 0, ',', ' '))
                ->description('Barcha vaqt davomida')
                ->descriptionIcon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->chart([$yesterdayDownloads, $todayDownloads]),

            Stat::make('Bugungi yuklab olinishlar', number_format($todayDownloads, 0, ',', ' '))
                ->description($change != 0 ? ($change > 0 ? "+{$change}%" : "{$change}%") : 'O\'zgarmadi')
                ->descriptionIcon($change > 0 ? 'heroicon-o-arrow-trending-up' : ($change < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus'))
                ->color($change > 0 ? 'success' : ($change < 0 ? 'danger' : 'gray'))
                ->chart([$yesterdayDownloads, $todayDownloads]),

            Stat::make('Oxirgi 7 kunlik yuklab olinishlar', number_format($weekDownloads, 0, ',', ' '))
                ->description('Haftalik statistika')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning'),
        ];
    }
}
