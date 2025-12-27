<?php

namespace App\Filament\Resources\SignatureSheetResource\RelationManagers;

use App\Models\Source;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use setasign\Fpdi\Fpdi;

class SourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'sources';

    protected static ?string $recordTitleAttribute = 'code';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('source.fields.code')),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('source.fields.label')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('customized_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->action(function (Source $record) {
                        $sheet = $this->getOwnerRecord();

                        if (!$sheet->sheet_pdf) {
                            Notification::make()
                                ->title('No PDF found on this sheet')
                                ->danger()
                                ->send();
                            return null;
                        }

                        $path = Storage::disk('public')->path($sheet->sheet_pdf);
                        if (!file_exists($path)) {
                            Notification::make()
                                ->title('PDF file is missing in storage')
                                ->danger()
                                ->send();
                            return null;
                        }

                        $tempDecompressedPath = tempnam(sys_get_temp_dir(), 'pdf_decomp_');

                        try {
                            // Use Ghostscript to decompress the PDF
                            $process = new Process([
                                'gs',
                                '-q',
                                '-dNOPAUSE',
                                '-dBATCH',
                                '-sDEVICE=pdfwrite',
                                '-dCompatibilityLevel=1.4',
                                '-sOutputFile=' . $tempDecompressedPath,
                                $path,
                            ]);

                            $process->mustRun();

                            if (!file_exists($tempDecompressedPath) || filesize($tempDecompressedPath) === 0) {
                                Notification::make()
                                    ->title('Failed to decompress PDF')
                                    ->danger()
                                    ->send();
                                return null;
                            }

                            // Now use FPDI on the decompressed PDF
                            $pdf = new Fpdi();
                            $pageCount = $pdf->setSourceFile($tempDecompressedPath);

                            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                                $tplId = $pdf->importPage($pageNo);
                                $size = $pdf->getTemplateSize($tplId);
                                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                                $pdf->useTemplate($tplId);

                                if ($pageNo === 1) {
                                    $pdf->SetFont('Courier', '', 14);
                                    $pdf->SetTextColor(0, 0, 0);
                                    $pdf->Text(193.5, 14, (string) $record->code);
                                }
                            }

                            $fileName = sprintf('%s-%s.pdf', $sheet->short_name ?? 'sheet', $record->code ?? 'source');

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->Output('S');
                            }, $fileName, [
                                'Content-Type' => 'application/pdf',
                            ]);
                        } catch (\Exception $e) {
                            @unlink($tempDecompressedPath);
                            Notification::make()
                                ->title('Error processing PDF: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return null;
                        }
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
