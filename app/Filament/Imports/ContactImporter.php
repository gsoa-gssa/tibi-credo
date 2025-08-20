<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use App\Models\Zipcode;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
              ->label(__('contact.fields.id'))
              ->requiredMapping()
              ->numeric()
              ->guess(['id', 'contact-id'])
              ->rules(['nullable', 'integer']),
            ImportColumn::make('firstname')
              ->label(__('contact.fields.firstname'))
              ->ignoreBlankState()
              ->rules(['max:255']),
            ImportColumn::make('lastname')
              ->label(__('contact.fields.lastname'))
              ->ignoreBlankState()
              ->rules(['max:255']),
            ImportColumn::make('street_no')
              ->label(__('contact.fields.street_no'))
              ->helperText(__('contact.importer.column.street_no'))
              ->guess(['strasse'])
              ->ignoreBlankState()
              ->rules(['max:255']),
            ImportColumn::make('zipcode')
              ->label(__('zipcode.name'))
              ->helperText(__('contact.importer.column.zipcode'))
              ->ignoreBlankState()
              ->relationship(resolveUsing: function (string $state): ?Zipcode {
                  return self::resolveZipcodeId($state);
              }),
            ImportColumn::make('lang')
              ->label(__('commune.fields.lang'))
              ->ignoreBlankState()
              ->rules(['in:de,fr,it']),
            ImportColumn::make('birthdate')
              ->label(__('contact.fields.birthdate'))
              ->ignoreBlankState()
              ->rules(['date']),
        ];
    }

    public function resolveRecord(): ?Contact
    {
        // Try to find existing contact by ID first
        $contact = Contact::firstOrNew([
            'id' => $this->data['id'],
        ]);

        return $contact;
    }

    /**
     * Resolve zipcode_id from zip code and place name
     */
    protected static function resolveZipcodeId(string $zip_and_place): ?Zipcode
    {
        $zip_and_place = trim($zip_and_place);
        [$zip, $place] = explode(' ', $zip_and_place, 2);

        $zip = trim($zip);
        $place = strtolower(trim($place));

        $zipcode = Zipcode::where('code', $zip)
          ->whereRaw('LOWER(name) = ?', $place)
          ->first();

        if ($zipcode) {
          return $zipcode;
        } else {
          return null;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}