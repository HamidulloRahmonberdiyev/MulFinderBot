<?php

namespace App\Filament\Widgets;

use App\Models\Film;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TodayFilmsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayCount = Film::whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Bugungi joylangan kinolar', $todayCount)
                ->description('Bugun qo\'shilgan kinolar soni')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),
        ];
    }
}

