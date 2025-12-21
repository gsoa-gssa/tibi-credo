<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Commune;
use App\Models\Canton;
use Illuminate\Support\Carbon;

class ImportBfsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'importBfs';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('BfS import')
            ->icon('heroicon-o-arrow-up-on-square')
            ->form([
                \Filament\Forms\Components\Placeholder::make('explanation')
                    ->label(__('commune.bfs_import.explanation_title'))
                    ->content(new HtmlString(__('commune.bfs_import.explanation'))),
                \Filament\Forms\Components\DatePicker::make('date')
                    ->label(__('commune.bfs_import.date'))
                    ->default(now())
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('file')
                    ->label(__('commune.import_file'))
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes(['text/csv','text/plain','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->required(),
            ])
            ->requiresConfirmation()
            ->action(function (array $data) {
                $path = $data['file'] ?? null;
                $checkedDate = Carbon::parse($data['date'])->toDateString();

                if (!$path) {
                    Notification::make()->danger()->title('No file').body('Please upload a file').send();
                    return;
                }

                $fullPath = storage_path('app/' . $path);
                if (!file_exists($fullPath)) {
                    Notification::make()->danger()->title('File missing').body('Uploaded file could not be found').send();
                    return;
                }

                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                $rows = [];
                try {
                    if (in_array($ext, ['csv', 'txt'])) {
                        $rows = $this->readCsv($fullPath);
                    } else {
                        $rows = $this->readSpreadsheet($fullPath);
                    }
                } catch (\Throwable $e) {
                    Notification::make()->danger()->title('Import failed').body($e->getMessage())->send();
                    return;
                }

                $updated = 0;
                $created = 0;
                $mismatches = 0;
                $missingCantons = 0;

                foreach ($rows as $row) {
                    $headers = array_change_key_case($row, CASE_LOWER);
                    $pk = trim($headers['bfs gde-nummer'] ?? '');
                    $cantonLabel = trim($headers['kanton'] ?? '');
                    $name = trim($headers['gemeindename'] ?? '');

                    if ($pk === '') {
                        continue;
                    }

                    $commune = Commune::where('officialId', (int) $pk)->with('canton')->first();
                    if ($commune) {
                        $nameMatch = trim($commune->name) === $commune->withoutCantonSuffix($name);
                        $cantonMatch = $commune->canton && $commune->canton->label === $cantonLabel;
                        if ($nameMatch && $cantonMatch) {
                            $commune->checked_on = $checkedDate;
                            $commune->save();
                            $updated++;
                        } else {
                            $mismatches++;
                        }
                    } else {
                        $canton = $cantonLabel !== '' ? Canton::where('label', $cantonLabel)->first() : null;
                        if (!$canton && $cantonLabel !== '') {
                            $missingCantons++;
                        }
                        Commune::create([
                            'name' => $name,
                            'officialId' => (int) $pk,
                            'canton_id' => $canton?->id,
                            'checked_on' => null,
                        ]);
                        $created++;
                    }
                }

                Notification::make()
                    ->success()
                    ->title('BfS import complete')
                    ->body("Updated: $updated, Created: $created, Mismatches: $mismatches, Missing cantons: $missingCantons")
                    ->send();

                // Clean up uploaded file
                try {
                    Storage::disk('local')->delete($path);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete import file: ' . $path);
                }
            });
    }

    private function readCsv(string $fullPath): array
    {
        $rows = [];
        $handle = fopen($fullPath, 'r');
        if (!$handle) return $rows;
        $firstLine = fgets($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        // rewind
        fseek($handle, 0);
        $headers = null;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($h) => trim($h), $data);
                continue;
            }
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function readSpreadsheet(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        $headers = null;
        $rows = [];
        foreach ($data as $row) {
            if ($headers === null) {
                $headers = array_map(fn($h) => trim((string)$h), $row);
                continue;
            }
            $assoc = [];
            $i = 0;
            foreach ($headers as $h) {
                $val = array_values($row)[$i] ?? null;
                $assoc[$h] = $val;
                $i++;
            }
            $rows[] = $assoc;
        }
        return $rows;
    }
}
