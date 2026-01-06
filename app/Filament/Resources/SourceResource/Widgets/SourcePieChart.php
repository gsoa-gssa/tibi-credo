<?php

namespace App\Filament\Resources\SourceResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Source;
use Illuminate\Support\Arr;

class SourcePieChart extends ChartWidget
{
    // Number of top sources to show in the chart
    protected static int $topSourcesCount = 10;

    // Allow per-instance column span (e.g., 'full')
    public array|string|int $columnSpan = 'full';

    // Optional: restrict to a specific signature_collection_id
    public ?int $signatureCollectionId = null;

    public function getHeading(): ?string
    {
        return __('widgets.source_pie_chart.heading');
    }

    protected function getData(): array
    {

        // Build the base query
        $sourceQuery = Source::query();
        if ($this->signatureCollectionId !== null) {
            $sourceQuery->where('signature_collection_id', $this->signatureCollectionId);
        }
        $sources = $sourceQuery->with(['countings' => function ($countingQuery) {
            if ($this->signatureCollectionId !== null) {
                $countingQuery->whereHas('source', function ($q) {
                    $q->where('signature_collection_id', $this->signatureCollectionId);
                });
            }
            $countingQuery->select('source_id')
                ->selectRaw('SUM(count) as total_count')
                ->groupBy('source_id');
        }])->get();

        $sourceData = [];
        foreach ($sources as $source) {
            $total = $source->countings->first()->total_count ?? 0;
            $sourceData[] = [
                'code' => $source->code,
                'total' => (int) $total,
            ];
        }

        // Sort descending by total
        usort($sourceData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });


        $topCount = static::$topSourcesCount;
        $top = array_slice($sourceData, 0, $topCount);
        $others = array_slice($sourceData, $topCount);

        $labels = array_column($top, 'code');
        $data = array_column($top, 'total');

        if (count($others) > 0) {
            $otherTotal = array_sum(array_column($others, 'total'));
            $labels[] = __('widgets.source_pie_chart.other_sources');
            $data[] = $otherTotal;
        }

        // Generate an evenly spaced color palette
        $colorCount = count($labels);
        $backgroundColors = [];
        for ($i = 0; $i < $colorCount; $i++) {
            $hue = (int) round(($i * 360) / max($colorCount, 1));
            $backgroundColors[] = "hsl($hue, 65%, 55%)";
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Total Count'),
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
