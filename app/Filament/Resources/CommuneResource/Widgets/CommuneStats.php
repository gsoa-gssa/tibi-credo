<?php

namespace App\Filament\Resources\CommuneResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CommuneStats extends BaseWidget
{

    public ?Model $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make(__("widgets.communeStats.signatures"), $this->record->sheets()->sum('signatureCount')),
            Stat::make(__("widgets.communeStats.sheets"), $this->record->sheets()->count()),
        ];
    }
}
