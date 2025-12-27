<?php

namespace App\Filament\Exports;

use App\Models\Commune;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CommuneExporter extends Exporter
{
    protected static ?string $model = Commune::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name'),
            ExportColumn::make('officialId'),
            ExportColumn::make('email'),
            ExportColumn::make('website'),
            ExportColumn::make('phone'),
            ExportColumn::make('authority_address_name')->label('Name of Authority'),
            ExportColumn::make('authority_address_street')->label('Street Name'),
            ExportColumn::make('authority_address_house_number')->label('House Number'),
            ExportColumn::make('authority_address_extra')->label('Extra Address Line'),
            ExportColumn::make('authority_address_postcode')->label('Postcode'),
            ExportColumn::make('authority_address_place')->label('Place'),
            ExportColumn::make('address_checked')->label('Address Checked'),
            ExportColumn::make('created_at')->enabledByDefault(false),
            ExportColumn::make('updated_at')->enabledByDefault(false),
            ExportColumn::make('lang'),
            ExportColumn::make('canton.label')->label('Canton'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your commune export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
