<?php

namespace App\Filament\Resources\SourceResource\Pages;

use App\Filament\Resources\SourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSource extends ViewRecord
{
    protected static string $resource = SourceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SourceResource\Widgets\SourceStats::make(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
