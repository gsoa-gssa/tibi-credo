<?php

namespace App\Filament\Resources\SignatureCollectionResource\Widgets;

use App\Models\Maeppli;
use App\Models\Box;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCollectionStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __("widgets.signature_count_stats.missing_valid"),
                max(
                    0,
                    auth()->user()->signatureCollection->valid_signatures_goal-Maeppli::sum('signatures_valid_count')
                )
            ),
            Stat::make(
                __("widgets.signature_count_stats.valid"),
                Maeppli::sum('signatures_valid_count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.invalid"),
                Maeppli::sum('signatures_invalid_count')
            ),
            Stat::make(
                __("widgets.box_stats.count"),
                Box::signature_count_all()
            ),
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}
