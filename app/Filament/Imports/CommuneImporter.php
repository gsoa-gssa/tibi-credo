<?php

namespace App\Filament\Imports;

use App\Models\Commune;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CommuneImporter extends Importer
{
    protected static ?string $model = Commune::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('officialId')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('email')
                ->requiredMapping(),
            ImportColumn::make('website')
                ->requiredMapping()
                ->rules(['max:255']),
            ImportColumn::make('phone')
                ->requiredMapping()
                ->rules(['max:255']),
            ImportColumn::make('authority_address_name')->requiredMapping(),
            ImportColumn::make('authority_address_street')->requiredMapping(),
            ImportColumn::make('authority_address_house_number')->requiredMapping(),
            ImportColumn::make('authority_address_extra')->requiredMapping(),
            ImportColumn::make('authority_address_postcode')->requiredMapping(),
            ImportColumn::make('authority_address_place')->requiredMapping(),
            ImportColumn::make('address_checked')->requiredMapping(),
            ImportColumn::make('lang'),
        ];
    }

    public function resolveRecord(): ?Commune
    {
        return Commune::firstOrNew([
            'id' => $this->data['id'],
        ]);

        // return new Commune();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your commune import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
