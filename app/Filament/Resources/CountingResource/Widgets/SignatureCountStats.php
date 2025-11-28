<?php

namespace App\Filament\Resources\CountingResource\Widgets;

use App\Models\Counting;
use App\Models\Maeppli;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCountStats extends BaseWidget
{
    protected static function requiredMin($required_valid): int
    {
        $validity_total = Maeppli::sum('sheets_valid_count');
        $validity_total_quotient = $validity_total + Maeppli::sum('sheets_invalid_count');
        if ( $validity_total_quotient == 0 ) {
            return 0;
        }
        $validity_total = $validity_total / $validity_total_quotient;
        return ceil( $required_valid / $validity_total );
    }

    protected static function requiredValidityByWorstMaeppli($required_valid, $ratio): int
    {
        $total_amount_of_maeppli = Maeppli::count();
        $number_of_worst_maeppli = max(1, (int) ceil($total_amount_of_maeppli * $ratio));

        // Select worst Maepplis by invalid/valid ratio (descending).
        // Handle division by zero by treating 0 valid as a very large ratio.
        $worstMaepplis = Maeppli::select('maepplis.*')
            ->selectRaw('COALESCE(1.0 * sheets_invalid_count / NULLIF(sheets_valid_count, 0), 999999.0) AS invalid_valid_ratio')
            ->orderByDesc('invalid_valid_ratio')
            ->limit($number_of_worst_maeppli)
            ->get();

        $validity_worst = $worstMaepplis->sum('sheets_valid_count');
        $validity_worst_quotient = $validity_worst + $worstMaepplis->sum('sheets_invalid_count');
        if ( $validity_worst_quotient == 0 ) {
            return 0;
        }
        $validity_worst = $validity_worst / $validity_worst_quotient;
        return ceil( $required_valid / $validity_worst );
    }
    protected function getStats(): array
    {
        $total_collected = Counting::sum('count');
        $required_valid = 103000;

        $required_min = self::requiredMin($required_valid);
        
        $required_worst_50 = self::requiredValidityByWorstMaeppli($required_valid, 0.5);

        $required_valid_extra = 106000;
        $required_min106 = self::requiredMin($required_valid_extra);
        $required_worst_50_extra = self::requiredValidityByWorstMaeppli($required_valid_extra, 0.5);
        return [
            Stat::make(
                __("widgets.signature_count_stats.total"),
                $total_collected
            ),
            Stat::make(
                __("widgets.signature_count_stats.total_valid_required"),
                $required_valid
            ),
            Stat::make(
                __("widgets.signature_count_stats.total_valid_required_extra"),
                $required_valid_extra
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_min"),
                max(0, $required_min-$total_collected)
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_min106"),
                max(0, $required_min106-$total_collected)
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_worst_50"),
                max(0, $required_worst_50-$total_collected)
            ),
            Stat::make(
                __("widgets.signature_count_stats.left_worst_50_extra"),
                max(0, $required_worst_50_extra-$total_collected)
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
