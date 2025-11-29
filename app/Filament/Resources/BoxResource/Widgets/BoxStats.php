<?php

namespace App\Filament\Resources\BoxResource\Widgets;

use App\Models\Box;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BoxStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __("widgets.box_stats.count"),
                0
            ),
        ];
    }
}
