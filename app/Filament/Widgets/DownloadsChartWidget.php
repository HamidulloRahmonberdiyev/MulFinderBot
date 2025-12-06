<?php

namespace App\Filament\Widgets;

use App\Models\Film;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DownloadsChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getHeading(): ?string
    {
        return 'Yuklab olinishlar statistikasi';
    }

    public function getDescription(): ?string
    {
        return 'Oxirgi 7 kunlik yuklab olinishlar statistikasi';
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        // Oxirgi 7 kunlik ma'lumotlar
        // Eslatma: Bu taxminiy hisob, chunki bizda aniq kunlik yuklab olish loglari yo'q
        // updated_at asosida hisoblaymiz
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d.m');

            // Bugungi yuklab olinishlar - updated_at bugun bo'lgan filmlarning downloads yig'indisi
            // Bu aniq emas, lekin taxminiy ko'rsatkich
            $count = DB::table('films')
                ->whereDate('updated_at', $date)
                ->sum('downloads');

            $data[] = (int) $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Yuklab olinishlar soni',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
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
        return 'bar';
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
