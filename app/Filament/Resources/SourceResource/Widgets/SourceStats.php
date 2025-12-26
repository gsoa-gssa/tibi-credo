<?php

namespace App\Filament\Resources\SourceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SourceStats extends BaseWidget
{

    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make(__("widgets.sourceStats.signatures"), $this->record->countings()->sum('count')),
            Stat::make(__("widgets.sourceStats.countings"), $this->record->countings()->count()),
        ];
    }
}
