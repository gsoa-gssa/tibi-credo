<?php

namespace App\Filament\Resources\BatchResource\Pages;

use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\BatchResource;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;

class ViewBatch extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make("exportLetter")
                ->label("Export Letter")
                ->icon("heroicon-o-envelope")
                ->action(function (Model $batch){
                    if ($batch->commune->address === null) {
                        Notification::make()
                            ->danger()
                            ->seconds(15)
                            ->title(
                                "The commune does not have an address. <a href=\"" .
                                route('filament.app.resources.communes.edit', $batch->commune) .
                                "\" class=\"underline\" target=\"_blank\">Add it here.</a>"
                            )
                            ->send();
                        return;
                    }
                    $pdf = Pdf::loadView('batch.letter-' . $batch->commune->lang, ['batch' => $batch]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'batch-letter-ID_' . $batch->id . '.pdf');
                }),
            ActionGroup::make([
                Action::make('edit')
                    ->label('Edit Batch')
                    ->icon('heroicon-o-pencil')
                    ->url(fn () => route('filament.app.resources.batches.edit', $this->record)),
                Action::make('markAsSent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (Model $batch) {
                         $batch->update([
                            'status' => 'sent',
                            'sendDate' => now(),
                         ]);
                         return redirect()->route('filament.app.resources.batches.index');
                    }),
            ])
        ];
    }
}
