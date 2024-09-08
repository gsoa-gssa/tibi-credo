<?php

namespace App\Filament\Resources\CountingResource\Widgets;

use App\Models\Counting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureCountStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__("widgets.signature_count_stats.total"), Counting::sum('count')),
            Stat::make(__("widgets.signature_count_stats.today"), Counting::whereDate('created_at', today())->sum('count')),
            Stat::make(__("widgets.signature_count_stats.month"), Counting::whereMonth('created_at', today())->sum('count')),
        ];
    }
}
