<?php

namespace App\Filament\Resources\SheetResource\Widgets;

use App\Models\Sheet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCountStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __("widgets.signature_count_stats.registered"),
                Sheet::sum('signatureCount')
            ),
            Stat::make(
                __("widgets.signature_count_stats.registered_with_batch"),
                Sheet::whereNotNull('batch_id')->sum('signatureCount')
            ),
            Stat::make(
                __("widgets.signature_count_stats.registered_with_maeppli"),
                Sheet::whereNotNull('maeppli_id')->sum('signatureCount')
            ),
        ];
    }
}
