<?php

namespace App\Filament\Resources\CountingResource\Widgets;

use App\Models\Counting;
use App\Models\Maeppli;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCountStats extends BaseWidget
{
    protected function getStats(): array
    {
        $validity_total = Maeppli::sum('sheets_valid_count');
        $validity_total = $validity_total / ($validity_total + Maeppli::sum('sheets_invalid_count'));
        $required_valid = 103000;
        $required_min = ceil( $required_valid / $validity_total );
        return [
            Stat::make(
                __("widgets.signature_count_stats.total"),
                Counting::sum('count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.required_min"),
                $required_min
            ),
            Stat::make(
                __("widgets.signature_count_stats.today"),
                Counting::whereDate('created_at', today())->sum('count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.month"),
                Counting::whereMonth('created_at', today())
                    ->whereYear('created_at', today())
                    ->sum('count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.last_month"),
                Counting::whereMonth('created_at', today()->subMonth())
                    ->whereYear('created_at', today()->subMonth())
                    ->sum('count')
            ),
        ];
    }
}
