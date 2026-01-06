<?php

namespace App\Filament\Resources\SignatureCollectionResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Counting;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class CountingChart extends ChartWidget
{
    protected array|string|int $columnSpan = 'full';

    /**
     * Validity factor for estimated signatures (optional).
     *
     * @var float|null
     */
    public ?float $validity = null;

    protected static string $view = 'filament.widgets.progress-chart';

    public ?string $startDate = null;
    public ?string $endDate = null;

    public ?string $minDate = null;
    public ?string $maxDate = null;


    public function getHeading(): ?string
    {
        if ($this->validity !== null) {
            return __('widgets.progress_chart.estimated_signatures');
        }
        return __('counting.namePlural');
    }

    public function mount(): void
    {
        // Only set if not already set (e.g. by Livewire/Filament)
        if ($this->endDate === null || $this->startDate === null) {
            $signatureCollection = auth()->user()?->signatureCollection;
            $start = $signatureCollection?->start_date ?? $signatureCollection?->publication_date ?? null;
            $end = $signatureCollection?->end_date ?? Carbon::today();
            $start = $start ? Carbon::parse($start)->startOfDay() : null;
            $end = $end ? Carbon::parse($end)->startOfDay() : Carbon::today();
            if (! $start) {
                $first = Counting::whereNotNull('date')->orderBy('date')->first();
                $start = $first ? Carbon::parse($first->date)->startOfDay() : $end->copy()->subDays(30);
            }
            $rows = $this->getDailyTotals($start, $end);
            $labels = [];
            $period = new \DatePeriod($start->toDateTimeImmutable(), new \DateInterval('P1D'), $end->addDay()->toDateTimeImmutable());
            foreach ($period as $dt) {
                $labels[] = Carbon::instance($dt)->toDateString();
            }
            $lastCountingDate = null;
            if (!empty($rows)) {
                $lastCountingDate = collect($rows)->keys()->max();
            }
            if (!$lastCountingDate && !empty($labels)) {
                $lastCountingDate = end($labels);
            }
            if ($lastCountingDate) {
                $lastCounting = Carbon::parse($lastCountingDate);
                if ($this->endDate === null) {
                    $this->endDate = $lastCounting->toDateString();
                }
                if ($this->startDate === null) {
                    $oneMonthBefore = $lastCounting->copy()->subMonth();
                    $graphStart = $labels[0] ?? $start->toDateString();
                    $defaultStart = $oneMonthBefore->lt(Carbon::parse($graphStart)) ? $graphStart : $oneMonthBefore->toDateString();
                    $this->startDate = $defaultStart;
                }
            }
        }
    }

    /**
     * Calculate overlay points based on first, second, and end point.
     *
     * @param int $firstIndex
     * @param int $secondIndex
     * @param int $endIndex
     * @param int $firstValue
     * @param int $secondValue
     * @return array
     */
    protected function calculateOverlayPoints($firstIndex, $secondIndex, $endIndex, $firstValue, $secondValue)
    {
        $overlay = array_fill(0, $endIndex + 1, null);
        if ($secondIndex === $firstIndex) {
            $overlay[$firstIndex] = $firstValue;
            return $overlay;
        }
        $days = $secondIndex - $firstIndex;
        $dailyRate = ($secondValue - $firstValue) / $days;
        $current = $firstValue;
        for ($i = $firstIndex; $i <= $endIndex; $i++) {
            $overlay[$i] = (int) round($current);
            $current += $dailyRate;
        }
        return $overlay;
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

        // If validity is set, multiply all data values by validity
        if ($this->validity !== null) {
            // Clamp validity to allowed range
            $validity = max(0.7, min(0.85, (float) $this->validity));
            $data = array_map(fn($v) => $v * $validity, $data);
        }

        // expose min/max dates for the view (used for input min/max)
        $this->minDate = $labels[0] ?? null;
        $this->maxDate = $labels[count($labels) - 1] ?? null;

        // build overlay dataset connecting startDate and endDate
        $overlay = array_fill(0, count($labels), null);
        if ($this->startDate || $this->endDate) {
            // use provided dates, swap if out of order
            $sDate = $this->startDate ? Carbon::parse($this->startDate) : null;
            $eDate = $this->endDate ? Carbon::parse($this->endDate) : null;
            if ($sDate && $eDate && $sDate->gt($eDate)) {
                [$sDate, $eDate] = [$eDate, $sDate];
            }

            $firstLabel = $labels[0] ?? null;
            $lastLabel = $labels[count($labels) - 1] ?? null;

            // helper to clamp a date to labels range and get index
            $getIndexFor = function (?Carbon $date) use ($labels, $firstLabel, $lastLabel, $data) {
                if (! $date) {
                    return null;
                }
                $ds = $date->toDateString();
                if ($firstLabel && $ds < $firstLabel) {
                    return 0;
                }
                if ($lastLabel && $ds > $lastLabel) {
                    return count($labels) - 1;
                }
                $idx = array_search($ds, $labels, true);
                if ($idx !== false) {
                    return $idx;
                }
                // fallback: find nearest by timestamp
                $ts = $date->getTimestamp();
                $best = null;
                $bestDiff = PHP_INT_MAX;
                foreach ($labels as $k => $lbl) {
                    $diff = abs($ts - Carbon::parse($lbl)->getTimestamp());
                    if ($diff < $bestDiff) {
                        $bestDiff = $diff;
                        $best = $k;
                    }
                }
                return $best;
            };

            $i = $getIndexFor($sDate);
            $j = $getIndexFor($eDate);
            $endIndex = count($labels) - 1;

            if ($i !== null && $j !== null) {
                if ($i > $j) {
                    [$i, $j] = [$j, $i];
                }
                $vI = max(0, $data[$i] ?? 0);
                $vJ = max(0, $data[$j] ?? 0);
                // Use the new overlay calculation function
                $overlay = $this->calculateOverlayPoints($i, $j, $endIndex, $vI, $vJ);
            } else {
                if ($i !== null) {
                    $overlay[$i] = max(0, $data[$i] ?? 0);
                }
                if ($j !== null) {
                    $overlay[$j] = max(0, $data[$j] ?? 0);
                }
            }
        }

        $datasets = [
            [
                'label' => $this->validity !== null
                    ? __('widgets.progress_chart.estimated_signatures')
                    : __('counting.namePlural'),
                'data' => $data,
                'fill' => false,
                'pointRadius' => 0,
                'borderWidth' => 0.5,
                'order' => 1,
            ],
        ];

        // add overlay line if any overlay points exist — ensure it's drawn on top
        if (array_filter($overlay, fn($v) => $v !== null)) {
            $datasets[] = [
                'label' => __('widgets.progress_chart.overlay'),
                'data' => $overlay,
                'borderDash' => [6, 2],
                'borderWidth' => 1,
                'spanGaps' => true,
                'pointRadius' => 0,
                'fill' => false,
                'order' => 2,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
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
        $cacheKey = 'counting_daily_totals_' . $start->toDateString() . '_' . $end->toDateString();
        return Cache::remember($cacheKey, 600, function () use ($start, $end) {
            return Counting::whereNotNull('date')
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
