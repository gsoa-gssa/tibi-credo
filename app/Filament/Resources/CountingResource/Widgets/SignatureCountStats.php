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
        $required_valid = 103000;

        $validity_total = Maeppli::sum('sheets_valid_count');
        $validity_total_quotient = $validity_total + Maeppli::sum('sheets_invalid_count');
        if ( $validity_total_quotient == 0 ) {
            $required_min = 0;
        } else {
            $validity_total = $validity_total / $validity_total_quotient;
            $required_min = ceil( $required_valid / $validity_total );
        }
        

        $validity_30days = Maeppli::where('created_at', '>=', now()->subDays(30))->sum('sheets_valid_count');
        $validity_30days_quotient = ($validity_30days + Maeppli::where('created_at', '>=', now()->subDays(30))->sum('sheets_invalid_count'));
        if ( $validity_30days_quotient == 0 ) {
            $required_min_30days = 0;
        } else {
            $validity_30days = $validity_30days / $validity_30days_quotient;
            $required_min_30days = ceil( $required_valid / $validity_30days );
        }
        return [
            Stat::make(
                __("widgets.signature_count_stats.total"),
                Counting::sum('count')
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_min"),
                max(0, $required_min-Counting::sum('count'))
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_30days"),
                max(0, $required_min_30days-Counting::sum('count'))
            ),
            Stat::make(
                __("widgets.signature_count_stats.required_min"),
                $required_min
            ),
            Stat::make(
                __("widgets.signature_count_stats.required_30days"),
                $required_min_30days
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
