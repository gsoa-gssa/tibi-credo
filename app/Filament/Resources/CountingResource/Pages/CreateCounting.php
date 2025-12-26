<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCounting extends CreateRecord
{
    protected static string $resource = CountingResource::class;
    protected static string $view = 'filament.pages.create-counting';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $state = $this->form->getState();
        $count = (int)($state['count'] ?? 0);
        $confirmed = (bool)($state['confirm_large_count'] ?? false);

        if ($count > 100 && ! $confirmed) {
            throw ValidationException::withMessages([
                'confirm_large_count' => __('Please confirm large count when count exceeds 100.'),
            ]);
        }

        return $data;
    }
}
