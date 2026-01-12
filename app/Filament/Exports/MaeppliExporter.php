<?php

namespace App\Filament\Exports;

use App\Models\Maeppli;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class MaeppliExporter extends Exporter
{
    protected static ?string $model = Maeppli::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::Make('label_number')
              ->label(__('maeppli.fields.label')),
            ExportColumn::make('commune_id'),
            ExportColumn::make('commune.name_with_canton')
              ->label(__('commune.name')),
            ExportColumn::make('commune.canton.label')
              ->label(__('canton.name')),
            ExportColumn::make('sheets_count')
              ->label(__('maeppli.fields.sheets_count')),
            ExportColumn::make('weight_grams')
              ->label(__('batch.fields.weight_grams')),
            ExportColumn::make('signatures_valid_count')
              ->label(__('maeppli.fields.signatures_valid_count')),
            ExportColumn::make('signatures_invalid_count')
              ->label(__('maeppli.fields.signatures_invalid_count')),
            ExportColumn::make('created_at')
              ->label(__('maeppli.fields.created_at')),
            ExportColumn::make('updated_at')
              ->label(__('maeppli.fields.updated_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your Maeppli export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
