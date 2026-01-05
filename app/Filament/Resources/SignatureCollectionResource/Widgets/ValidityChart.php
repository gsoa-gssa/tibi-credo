<?php

namespace App\Filament\Resources\SignatureCollectionResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Maeppli;
use Illuminate\Support\Carbon;

class ValidityChart extends LineChartWidget
{
    protected array|string|int $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('widgets.validity_chart.heading');
    }

    protected function yearWeeks($start, $end)
    {
        // Generate all year_weeks between $start and $end, matching SQL's YEARWEEK(..., 3)
        $allWeeks = [];
        $current = Carbon::parse($start)->startOfWeek(Carbon::MONDAY);
        $endDate = Carbon::parse($end)->endOfWeek(Carbon::SUNDAY);
        while ($current->lte($endDate)) {
            $yearWeek = $current->format('oW'); // ISO-8601 year and week number, matches YEARWEEK(..., 3)
            $allWeeks[] = [
                'year_week' => $yearWeek,
                'week_start' => $current->format('Y-m-d'),
            ];
            $current->addWeek();
        }
        return $allWeeks;
    }

    // Convert a YEARWEEK string (e.g. 202601) to a representative date (first day of that yearweek)
    protected function yearWeekToDate($yearWeek)
    {
        // $yearWeek is like 202601 (YYYYWW)
        $year = substr($yearWeek, 0, 4);
        $week = substr($yearWeek, 4, 2);
        // Use ISO week date: week starts on Monday
        $date = new Carbon();
        $date->setISODate($year, $week);
        return $date->format('Y-m-d');
    }

    /**
     * Calculates the weekly average validity for a given signature collection within a specified date range.
     *
     * @param SignatureCollection $collection The signature collection to analyze.
     * @param Carbon $start The start date of the range.
     * @param Carbon $end The end date of the range.
     * @return array<int, array{week_start: string, avg_validity: float|null}> Each element is an associative array with keys 'week_start' (YYYY-MM-DD) and 'avg_validity' (float|null).
     */
    protected function weeklyAverageValidity($collection, $start, $end)
    {
        // Group by week and calculate average validity for each week
        $results = Maeppli::where('signature_collection_id', $collection->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                YEARWEEK(created_at, 3) as year_week,
                AVG(signatures_valid_count / NULLIF((signatures_valid_count + signatures_invalid_count), 0)) as avg_validity
            ')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        // Map: year_week => avg_validity
        $validityByYearWeek = [];
        foreach ($results as $row) {
            $validityByYearWeek[$row->year_week] = $row->avg_validity;
        }

        $allWeeks = $this->yearWeeks($start, $end);

        // Fill in null for missing weeks, and translate year_week to a representative date (first day of yearweek)
        $result = [];
        foreach ($allWeeks as $week) {
            $result[] = [
                'week_start' => $this->yearWeekToDate($week['year_week']),
                'avg_validity' => $validityByYearWeek[$week['year_week']] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Calculates the weekly cumulative total of certified signatures for a given signature collection within a specified date range.
     * 
     */
    protected function weeklyTotalCertified($collection, $start, $end)
    {
        // Get weekly sum of only valid certified signatures per yearweek
        $results = Maeppli::where('signature_collection_id', $collection->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                YEARWEEK(created_at, 3) as year_week,
                SUM(signatures_valid_count) as weekly_certified
            ')
            ->groupBy('year_week')
            ->orderBy('year_week')
            ->get();

        // Map: year_week => weekly_certified
        $certifiedByYearWeek = [];
        foreach ($results as $row) {
            $certifiedByYearWeek[$row->year_week] = (int) $row->weekly_certified;
        }

        $allWeeks = $this->yearWeeks($start, $end);

        // Build cumulative total, filling null for missing weeks
        $result = [];
        $cumulative = 0;
        foreach ($allWeeks as $week) {
            $weekCertified = $certifiedByYearWeek[$week['year_week']] ?? 0;
            $cumulative += $weekCertified;
            $result[] = [
                'week_start' => $this->yearWeekToDate($week['year_week']),
                'cumulative_certified' => $cumulative,
            ];
        }
        return $result;
    }

    /**
     * Calculates the weekly average validity for the best and worst percentiles of Maepplis per week.
     *
     * @param SignatureCollection $collection
     * @param Carbon $start
     * @param Carbon $end
     * @param float $percentile_best (e.g. 0.5 = top 50%)
     * @param float $percentile_worst (e.g. 0.5 = bottom 50%)
     * @return array<int, array{week_start: string, best_validity: float|null, worst_validity: float|null}>
     */
    protected function weeklyBestWorstValidity($collection, $start, $end, $percentile_best = 0.5, $percentile_worst = 0.5)
    {
        // Get all Maepplis in range, with their week
        $maepplis = Maeppli::where('signature_collection_id', $collection->id)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'signatures_valid_count', 'signatures_invalid_count']);

        // Group by yearweek
        $byWeek = [];
        foreach ($maepplis as $m) {
            $yearWeek = Carbon::parse($m->created_at)->format('oW');
            $validity = null;
            $total = $m->signatures_valid_count + $m->signatures_invalid_count;
            if ($total > 0) {
                $validity = $m->signatures_valid_count / $total;
            }
            $byWeek[$yearWeek][] = $validity;
        }

        $allWeeks = $this->yearWeeks($start, $end);
        $result = [];
        foreach ($allWeeks as $week) {
            $vals = $byWeek[$week['year_week']] ?? [];
            // Filter out nulls
            $vals = array_filter($vals, fn($v) => $v !== null);
            $best = null;
            $worst = null;
            if (count($vals) > 0) {
                // Best percentile (highest validity)
                rsort($vals);
                $count = count($vals);
                $topCount = max(1, (int) round($count * $percentile_best));
                $topVals = array_slice($vals, 0, $topCount);
                $best = array_sum($topVals) / count($topVals);

                // Worst percentile (lowest validity)
                sort($vals);
                $bottomCount = max(1, (int) round($count * $percentile_worst));
                $bottomVals = array_slice($vals, 0, $bottomCount);
                $worst = array_sum($bottomVals) / count($bottomVals);
            }
            $result[] = [
                'week_start' => $this->yearWeekToDate($week['year_week']),
                'best_validity' => $best,
                'worst_validity' => $worst,
            ];
        }
        return $result;
    }

    /**
     * For each week: see how many valid signatures are still missing to reach the required total, and calculate how many signatures still need to be collected to get the required valid, based on best/worst validity projections. Then calculate what the overall validity would be in each case. Return those two data series.
     */
    protected function weeklyBestWorstValidityExtendedToRest($collection, $start, $end, $totalRequired, $percentile_best = 0.5, $percentile_worst = 0.5)
    {
        // Get cumulative valid signatures per week
        $cumulativeValid = $this->weeklyTotalCertified($collection, $start, $end);
        $bestWorst = $this->weeklyBestWorstValidity($collection, $start, $end, $percentile_best, $percentile_worst);
        $average = $this->weeklyAverageValidity($collection, $start, $end);

        $result = [];
        $count = count($cumulativeValid);
        for ($i = 0; $i < $count; $i++) {
            $week = $cumulativeValid[$i]['week_start'];
            $validSoFar = $cumulativeValid[$i]['cumulative_certified'];
            $best = $bestWorst[$i]['best_validity'];
            $worst = $bestWorst[$i]['worst_validity'];
            $avgValiditySoFar = $average[$i]['avg_validity'];

            // if any of those values is null, return null for this week
            if ($validSoFar === null || $best === null || $worst === null || $avgValiditySoFar === null || $avgValiditySoFar < 0.4 || $validSoFar >= $totalRequired) {
                $result[] = [
                    'week_start' => $week,
                    'projected_validity_best' => null,
                    'projected_validity_worst' => null,
                ];
                continue;
            }

            // calculate invalid so far using validSoFar and avg validity
            $invalidSoFar = $validSoFar * (1 - $avgValiditySoFar) / $avgValiditySoFar;
            $totalSoFar = $validSoFar + $invalidSoFar;

            $missingValid = max(0, $totalRequired - $validSoFar);
            $missingInvalidWorst = $missingValid * (1 - $worst) / $worst;
            $missingInvalidBest = $missingValid * (1 - $best) / $best;

            $total_validity_worst = $totalRequired / ( $totalSoFar + $missingValid + $missingInvalidWorst );
            $total_validity_best = $totalRequired / ( $totalSoFar + $missingValid + $missingInvalidBest );

            $result[] = [
                'week_start' => $week,
                'projected_validity_best' => $total_validity_best,
                'projected_validity_worst' => $total_validity_worst,
            ];
        }
        return $result;
    }

    protected function getData(): array
    {
        // Scaffold: Fetch Maeppli records and compute datasets
        // 1. For each week in the signature collection period, compute:
        //    - Average validity so far
        //    - Projected average validity (worse half)
        //    - Projected average validity (better half)
        //
        // You can fill in the actual computation logic as needed.

        $collection = auth()->user()->signatureCollection;
        if (!$collection) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $startDate = $collection->publication_date;
        $endDate = $collection->end_date;
        $totalRequired = 103000;//$collection->required_signature_count;
        $percentile_best = 0.4;
        $percentile_worst = 0.4;

        $weeklyData = $this->weeklyAverageValidity($collection, $startDate, $endDate);
        $bestWorstData = $this->weeklyBestWorstValidity($collection, $startDate, $endDate, $percentile_best, $percentile_worst);
        $projectedData = $this->weeklyBestWorstValidityExtendedToRest($collection, $startDate, $endDate, $totalRequired, $percentile_best, $percentile_worst);

        $labels = array_map(fn($row) => $row['week_start'], $weeklyData);
        $averageValidity = array_map(fn($row) => $row['avg_validity'], $weeklyData);
        $bestValidity = array_map(fn($row) => $row['best_validity'], $bestWorstData);
        $worstValidity = array_map(fn($row) => $row['worst_validity'], $bestWorstData);
        $projectedBest = array_map(fn($row) => $row['projected_validity_best'], $projectedData);
        $projectedWorst = array_map(fn($row) => $row['projected_validity_worst'], $projectedData);

        return [
            'datasets' => [
                [
                    'label' => __('widgets.validity_chart.avg'),
                    'data' => $averageValidity,
                    'borderWidth' => 2,
                ],
                [
                    'label' => __('widgets.validity_chart.best', ['percent' => $percentile_best * 100]),
                    'data' => $bestValidity,
                    'borderWidth' => 0,
                ],
                [
                    'label' => __('widgets.validity_chart.worst', ['percent' => $percentile_worst * 100]),
                    'data' => $worstValidity,
                    'borderWidth' => 0,
                ],
                [
                    'label' => 'Projected Validity (Best)',
                    'data' => $projectedBest,
                    'borderWidth' => 0.5,
                    'pointRadius' => 0,
                    'borderDash' => [6, 2],
                    'fill' => '0',
                ],
                [
                    'label' => 'Projected Validity (Worst)',
                    'data' => $projectedWorst,
                    'borderWidth' => 0.5,
                    'pointRadius' => 0,
                    'borderDash' => [6, 2],
                    'fill' => '0',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'zoom' => [
                    'pan' => [
                        'enabled' => true,
                        'mode' => 'x', // or 'xy' for both axes
                    ],
                    'zoom' => [
                        'wheel' => [ 'enabled' => true ],
                        'pinch' => [ 'enabled' => true ],
                        'mode' => 'x', // or 'xy' for both axes
                    ],
                ],
            ],
        ];
    }
}
