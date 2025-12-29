<?php

namespace App\Filament\Resources\BatchResource\Pages;

use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\BatchResource;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;

class ViewBatch extends ViewRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make("exportLetterLeftA4")
                    ->label(__('batch.action.exportLetterLeftA4'))
                    ->icon("heroicon-o-envelope")
                    ->action(function (Model $batch){
                        $batch->get_letter_html('left', 'B1');
                        return $batch->get_letter_pdf();
                    }),
                Action::make("exportLetterLeftA4")
                    ->label(__('batch.action.exportLetterLeftA4MassDelivery'))
                    ->icon("heroicon-o-envelope")
                    ->action(function (Model $batch){
                        $batch->get_letter_html('left', 'B2');
                        return $batch->get_letter_pdf();
                    }),
                Action::make("exportLetterLeftA4Priority")
                    ->label(__('batch.action.exportLetterLeftA4Priority'))
                    ->icon("heroicon-o-envelope")
                    ->action(function (Model $batch){
                        $batch->get_letter_html('left', 'A');
                        return $batch->get_letter_pdf();
                    }),
                Action::make('exportLetterRightA4')
                    ->label(__('batch.action.exportLetterRightA4'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->get_letter_html('right', 'B1');
                        return $batch->get_letter_pdf();
                    }),
                Action::make('exportLetterRightA4')
                    ->label(__('batch.action.exportLetterRightA4MassDelivery'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->get_letter_html('right', 'B2');
                        return $batch->get_letter_pdf();
                    }),
                Action::make('exportLetterRightA4Priority')
                    ->label(__('batch.action.exportLetterRightA4Priority'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->get_letter_html('right', 'A');
                        return $batch->get_letter_pdf();
                    }),
            ])
            ->button()
            ->label(__('batch.action.exportLetter')),
            Action::make('setLetterHtmlNull')
                    ->label(__('batch.action.setLetterHtmlNull'))
                    ->icon('heroicon-o-trash')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->requiresConfirmation()
                    ->action(function (Model $batch) {
                        $batch->letter_html = null;
                        $batch->save();
                        Notification::make()
                            ->title(__('batch.notification.letter_html_cleared'))
                            ->success()
                            ->send();
                    }),
            Action::make('edit')
                ->label(__('batch.action.editBatch'))
                ->icon('heroicon-o-pencil')
                ->url(fn () => route('filament.app.resources.batches.edit', $this->record)),
        ];
    }
}
