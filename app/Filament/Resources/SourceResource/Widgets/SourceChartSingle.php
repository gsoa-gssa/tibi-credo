<?php

namespace App\Filament\Resources\SourceResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Counting;
use Illuminate\Support\Arr;


class SourceChartSingle extends ChartWidget
{
    protected array|string|int $columnSpan = 'full';

    // The current source record (injected by Filament)
    public $record;

    public function getHeading(): ?string
    {
        return __('widgets.source_chart_single.heading');
    }

    protected function getData(): array
    {
              // determine date range from widget properties or user's signature collection
        $signatureCollection = auth()->user()?->signatureCollection;

        // Always build the full graph range from the signature collection (or data),
        // ignore the widget form fields for determining the graph bounds.
        $start = $signatureCollection?->publication_date ?? null;
        $end = $signatureCollection?->end_date ?? Carbon::today();

        // if $start is null throw an error, not displaying widget
        if ($start === null) {
            throw new \Exception('Cannot display CountingChart widget: start date is not defined.');
        }

        // load daily totals within the range
        $rows = $this->getDailyTotals($start, $end);

        // build continuous labels and cumulative data for every day in range
        $labels = [];
        $data = [];
        $running = 0;

        $period = new \DatePeriod($start->toDateTimeImmutable(), new \DateInterval('P1D'), $end->addDay()->toDateTimeImmutable());
        foreach ($period as $dt) {
            $date = Carbon::instance($dt)->toDateString();
            $daily = (int) Arr::get($rows, $date . '.daily_total', 0);
            $running += $daily;
            $labels[] = $date;
            $data[] = $running;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Cumulative Objects'),
                    'data' => $data,
                    'fill' => true,
                    'pointRadius' => 0.5,
                ],
            ],
        ];
    }

    /**
     * Get daily totals from the database, cached for 10 minutes.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return \Illuminate\Support\Collection
     */
    protected function getDailyTotals(Carbon $start, Carbon $end)
    {
        $sourceId = $this->record->id ?? null;
        $cacheKey = 'counting_daily_totals_' . $sourceId . '_' . $start->toDateString() . '_' . $end->toDateString();
        return Cache::remember($cacheKey, 600, function () use ($start, $end, $sourceId) {
            return Counting::whereNotNull('date')
                ->where('source_id', $sourceId)
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<=', $end->toDateString())
                ->selectRaw('date as date, SUM(`count`) as daily_total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy(fn($r) => Carbon::parse($r->date)->toDateString());
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
