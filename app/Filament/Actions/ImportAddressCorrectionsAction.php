<?php

namespace App\Filament\Actions;

use App\Models\Commune;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportAddressCorrectionsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_address_corrections';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label(__('commune.import_address_corrections'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->form([
                Placeholder::make('explanation')
                    ->label(__('commune.import_address_corrections_explanation_title'))
                    ->content(__('commune.import_address_corrections_explanation')),
                FileUpload::make('file')
                    ->label(__('commune.import_file'))
                    ->required()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->disk('local')
                    ->directory('imports'),
            ])
            ->action(function (array $data) {
                try {
                    $filePath = storage_path('app/' . $data['file']);
                    
                    if (!file_exists($filePath)) {
                        Notification::make()
                            ->danger()
                            ->title(__('commune.import_failed'))
                            ->body(__('commune.import_file_not_found'))
                            ->send();
                        return;
                    }

                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray(null, true, true, true);

                    // Find column indexes by header name
                    $header = array_shift($rows);
                    
                    // Trim whitespace from all headers
                    $header = array_map('trim', $header);
                    
                    $colMap = [
                        'Kunden-ID' => null,
                        'QSTAT Erklärung' => null,
                        'Firmenname' => null,
                        'Strasse' => null,
                        'Hausnummer' => null,
                        'Hausnummerzusatz' => null,
                        'PLZ' => null,
                        'Ort' => null,
                    ];
                    
                    // Debug: compare header values with colMap keys
                    foreach ($header as $col => $name) {
                        foreach (array_keys($colMap) as $expectedName) {
                            if (strtolower($name) === strtolower($expectedName)) {
                                $colMap[$expectedName] = $col;
                                break;
                            }
                        }
                    }
                    
                    // Check for missing columns
                    $missingColumns = [];
                    foreach ($colMap as $name => $col) {
                        if ($col === null) {
                            $missingColumns[] = $name;
                        }
                    }
                    
                    if (!empty($missingColumns)) {
                        $foundHeaders = implode(', ', array_filter($header));
                        Notification::make()
                            ->danger()
                            ->title(__('commune.import_failed'))
                            ->body(__('commune.import_missing_columns') . ': ' . implode(', ', $missingColumns) . "\n\nGefundene Spalten: " . $foundHeaders)
                            ->send();
                        return;
                    }

                    $updated = 0;
                    $skipped = 0;
                    foreach ($rows as $row) {
                        $kundenId = $row[$colMap['Kunden-ID']] ?? null;
                        if (!$kundenId) {
                            $skipped++;
                            continue;
                        }
                        
                        $commune = Commune::find($kundenId);
                        if (!$commune) {
                            $skipped++;
                            continue;
                        }

                        $commune->authority_address_name = $row[$colMap['Firmenname']] ?? null;
                        $commune->authority_address_street = $row[$colMap['Strasse']] ?? null;
                        $houseNum = $row[$colMap['Hausnummer']] ?? '';
                        $houseAdd = $row[$colMap['Hausnummerzusatz']] ?? '';
                        $commune->authority_address_house_number = trim($houseNum . $houseAdd) ?: null;
                        $commune->authority_address_postcode = $row[$colMap['PLZ']] ?? null;
                        $commune->authority_address_place = $row[$colMap['Ort']] ?? null;
                        $commune->address_checked = in_array(
                            $row[$colMap['QSTAT Erklärung']] ?? '',
                            ['Firmentreffer', 'Umzugstreffer'],
                            true
                        );
                        $commune->save();
                        $updated++;
                    }

                    // Clean up the uploaded file
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Notification::make()
                        ->success()
                        ->title(__('commune.import_success'))
                        ->body(__('commune.import_success_message', ['count' => $updated, 'skipped' => $skipped]))
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('commune.import_failed'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
