<?php

namespace App\Filament\Resources\BatchResource\Widgets;

use App\Models\Batch;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class BatchesByCreationDateChart extends ChartWidget
{
    protected static ?string $maxHeight = '300px';

    protected array|string|int $columnSpan = 'full';

    public function getHeading(): ?string
    {
        $total = Batch::query()->count();

        return __('widgets.batchesByCreationDate.heading', [
            'total' => number_format($total),
        ]);
    }

    protected function getData(): array
    {
        $rows = Batch::query()
            ->selectRaw('DATE(created_at) as creation_date, COUNT(*) as total')
            ->groupBy('creation_date')
            ->orderBy('creation_date')
            ->pluck('total', 'creation_date');

        $labels = $rows->keys()->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();
        $data = $rows->values()->map(fn ($v) => (int) $v)->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('widgets.batchesByCreationDate.dataset_label'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}