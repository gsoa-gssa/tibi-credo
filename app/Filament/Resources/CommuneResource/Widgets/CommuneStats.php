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
            Stat::make(
                __("widgets.communeStats.signatures"), 
                $this->record->batches()->sum('signature_count')
            ),
            Stat::make(
                __("widgets.communeStats.valid"),
                \App\Models\Maeppli::where('commune_id', $this->record->id)->sum('sheets_valid_count')
            ),
            Stat::make(
                __("widgets.communeStats.invalid"),
                \App\Models\Maeppli::where('commune_id', $this->record->id)->sum('sheets_invalid_count')
            ),
            Stat::make(
                __("widgets.communeStats.sheets"),
                $this->record->batches()->sum('sheets_count')
            ),
        ];
    }
}
