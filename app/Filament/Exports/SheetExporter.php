<?php

namespace App\Filament\Exports;

use App\Models\Sheet;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SheetExporter extends Exporter
{
    protected static ?string $model = Sheet::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('label'),
            ExportColumn::make('source.code'),
            ExportColumn::make('signatureCount'),
            ExportColumn::make('commune.name'),
            ExportColumn::make('vox'),
            ExportColumn::make('created_at'),
            ExportColumn::make('user.name')
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sheet export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
