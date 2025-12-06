<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DownloadsChartWidget;
use App\Filament\Widgets\DownloadsStatsWidget;
use App\Filament\Widgets\SearchesChartWidget;
use App\Filament\Widgets\SearchesStatsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            SearchesStatsWidget::class,
            SearchesChartWidget::class,
            DownloadsStatsWidget::class,
            DownloadsChartWidget::class,
        ];
    }
}
