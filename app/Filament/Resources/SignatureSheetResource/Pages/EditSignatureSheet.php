<?php

namespace App\Filament\Resources\SignatureSheetResource\Pages;

use App\Filament\Resources\SignatureSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSignatureSheet extends EditRecord
{
    protected static string $resource = SignatureSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Keep FK aligned with the authenticated user's collection; prevent tampering.
        $data['signature_collection_id'] = auth()->user()?->signature_collection_id;
        return $data;
    }
}
