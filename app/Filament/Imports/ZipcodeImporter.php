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
        ];
    }

    public function resolveRecord(): ?Zipcode
    {
        // return Zipcode::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Zipcode();
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
