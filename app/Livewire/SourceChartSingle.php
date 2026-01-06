<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Counting;
use App\Models\Source;
use Illuminate\Support\Arr;

class SourceChartSingle extends Component
{
    public Source $source;

    public function render()
    {
        $signatureCollection = $this->source->signatureCollection;
        $start = $signatureCollection?->publication_date ?? null;
        $end = $signatureCollection?->end_date ?? Carbon::today();

        if ($start === null) {
            return view('livewire.source-chart-single', [
                'error' => 'Cannot display chart: start date is not defined.',
            ]);
        }

        $rows = $this->getDailyTotals($start, $end);
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

        return view('livewire.source-chart-single', [
            'labels' => $labels,
            'data' => $data,
            'source' => $this->source,
            'error' => null,
        ]);
    }

    protected function getDailyTotals($start, $end)
    {
        $sourceId = $this->source->id ?? null;
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
}
