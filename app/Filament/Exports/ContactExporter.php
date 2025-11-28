<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Log;


class ContactExporter extends Exporter
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('contact.fields.id')),
            ExportColumn::make('firstname')
                ->label(__('contact.fields.firstname')),
            ExportColumn::make('lastname')
                ->label(__('contact.fields.lastname')),
            ExportColumn::make('street_no')
                ->label(__('contact.fields.street_no')),
            ExportColumn::make('zipcode.code')
                ->label(__('zipcode.fields.code'))
                ->state(fn (Contact $record): string => $record->zipcode?->code ?? ''),
            ExportColumn::make('zipcode.name')
                ->label(__('zipcode.fields.name'))
                ->state(fn (Contact $record): string => $record->zipcode?->name ?? ''),
            ExportColumn::make('birthdate')
                ->label(__('contact.fields.birthdate'))
                ->formatStateUsing(fn ($state) => (string) $state),
            ExportColumn::make('sheet_id')
                ->label(__('sheet.fields.id')),
            ExportColumn::make('address_corrected')
                ->label(__('contact.fields.address_corrected')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
