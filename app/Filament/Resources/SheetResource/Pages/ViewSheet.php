<?php

namespace App\Filament\Resources\SheetResource\Pages;

use App\Filament\Resources\SheetResource;
use Filament\Actions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Parallax\FilamentComments\Actions\CommentsAction;

class ViewSheet extends ViewRecord
{
    protected static string $resource = SheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommentsAction::make(),
            EditAction::make()
        ];
    }
}
