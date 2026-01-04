<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            \App\Filament\Resources\SignatureCollectionResource\Widgets\SignatureCollectionStats::class,
            \App\Filament\Resources\SignatureCollectionResource\Widgets\CountingChart::class,
            \App\Filament\Resources\SignatureCollectionResource\Widgets\ValidityChart::class,
        ];
    }

    public function getColumns(): int
    {
        return 4;
    }
}
