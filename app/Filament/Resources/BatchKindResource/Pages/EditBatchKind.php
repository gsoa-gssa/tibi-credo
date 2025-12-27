<?php

namespace App\Filament\Resources\BatchKindResource\Pages;

use App\Filament\Resources\BatchKindResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatchKind extends EditRecord
{
    protected static string $resource = BatchKindResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
