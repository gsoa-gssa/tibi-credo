<?php

namespace App\Filament\Resources\SignatureSheetResource\Pages;

use App\Filament\Resources\SignatureSheetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignatureSheet extends CreateRecord
{
    protected static string $resource = SignatureSheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['signature_collection_id'] = auth()->user()?->signature_collection_id;
        return $data;
    }
}
