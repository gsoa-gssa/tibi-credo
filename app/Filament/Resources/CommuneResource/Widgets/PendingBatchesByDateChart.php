<?php

namespace App\Filament\Resources\CommuneResource\Widgets;

use App\Models\Batch;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PendingBatchesByDateChart extends ChartWidget
{
    protected static ?string $maxHeight = '300px';

    protected array|string|int $columnSpan = 'full';

    public function getHeading(): ?string
    {
        $today = Carbon::today()->toDateString();

        $total = Batch::query()
            ->where('open', true)
            ->whereNotNull('expected_return_date')
            ->count();

        $overdue = Batch::query()
            ->where('open', true)
            ->whereNotNull('expected_return_date')
            ->whereDate('expected_return_date', '<', $today)
            ->count();

        return __('widgets.pendingBatchesByDate.heading', [
            'total' => number_format($total),
            'overdue' => number_format($overdue),
        ]);
    }

    protected function getData(): array
    {
        $rows = Batch::query()
            ->where('open', true)
            ->whereNotNull('expected_return_date')
            ->selectRaw('DATE(expected_return_date) as due_date, COUNT(*) as total')
            ->groupBy('due_date')
            ->orderBy('due_date')
            ->pluck('total', 'due_date');

        $labels = $rows->keys()->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();
        $data   = $rows->values()->map(fn ($v) => (int) $v)->toArray();

        return [
            'datasets' => [
                [
                    'label'           => __('widgets.pendingBatchesByDate.dataset_label'),
                    'data'            => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor'     => 'rgba(59, 130, 246, 1)',
                    'borderWidth'     => 1,
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
