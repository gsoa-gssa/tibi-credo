<?php

namespace App\Filament\Resources\BatchResource\Widgets;

use App\Models\Batch;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SignaturesByCreationDateChart extends ChartWidget
{
    protected static ?string $maxHeight = '300px';

    protected array|string|int $columnSpan = 'full';

    public function getHeading(): ?string
    {
        $total = Batch::query()->sum('signature_count');

        return __('widgets.signaturesByCreationDate.heading', [
            'total' => number_format((int) $total),
        ]);
    }

    protected function getData(): array
    {
        $rows = Batch::query()
            ->selectRaw('DATE(created_at) as creation_date, SUM(signature_count) as total')
            ->groupBy('creation_date')
            ->orderBy('creation_date')
            ->pluck('total', 'creation_date');

        $labels = $rows->keys()->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();
        $data = $rows->values()->map(fn ($v) => (int) $v)->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('widgets.signaturesByCreationDate.dataset_label'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.6)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
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