<?php

namespace App\Filament\Widgets;

use App\Models\Search;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SearchesChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return 'Qidiruvlar statistikasi';
    }

    public function getDescription(): ?string
    {
        return 'Oxirgi 7 kunlik qidiruvlar statistikasi';
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        // Oxirgi 7 kunlik ma'lumotlar
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d.m');

            $count = Search::whereDate('created_at', $date)->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Qidiruvlar soni',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
