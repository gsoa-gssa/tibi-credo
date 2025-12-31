<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCounting extends CreateRecord
{
    protected static string $resource = CountingResource::class;

    public $srcAndCount = '';
    public $source_id;
    public $signatureCount = 0;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
