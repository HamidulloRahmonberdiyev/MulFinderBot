<?php

namespace App\Filament\Widgets;

use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TodayStoriesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayCount = Story::whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Bugungi joylangan istoriyalar', $todayCount)
                ->description('Bugun qo\'shilgan istoriyalar soni')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('danger'),
        ];
    }
}

