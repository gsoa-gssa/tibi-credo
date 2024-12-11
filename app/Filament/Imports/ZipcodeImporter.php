<?php

namespace App\Filament\Imports;

use App\Models\Zipcode;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ZipcodeImporter extends Importer
{
    protected static ?string $model = Zipcode::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('ID')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('commune')
                ->requiredMapping()
                ->relationship(resolveUsing: "officialId")
                ->rules(['required']),
            ImportColumn::make('number_of_dwellings')
                ->requiredMapping()
                ->numeric(),
        ];
    }

    public function resolveRecord(): ?Zipcode
    {
        return Zipcode::firstOrNew([
            'id' => $this->data['ID'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your zipcode import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
