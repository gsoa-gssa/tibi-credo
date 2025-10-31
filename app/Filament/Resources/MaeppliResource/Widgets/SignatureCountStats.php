<?php

namespace App\Filament\Resources\MaeppliResource\Widgets;

use App\Models\Maeppli;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCountStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __("widgets.signature_count_stats.missing_valid"),
                max(0, 103000-Maeppli::sum('sheets_valid_count'))
            ),
            Stat::make(
                __("widgets.signature_count_stats.valid"),
                Maeppli::sum('sheets_valid_count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.invalid"),
                Maeppli::sum('sheets_invalid_count')
            ),
        ];
    }
}
