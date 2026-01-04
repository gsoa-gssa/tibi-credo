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
                Action::make('viewLetterLeftA4')
                    ->label(__('batch.action.exportLetterLeftA4'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'left',
                        'priority' => 'B1',
                    ]))
                    ->openUrlInNewTab(),
                Action::make('viewLetterLeftA4MassDelivery')
                    ->label(__('batch.action.exportLetterLeftA4MassDelivery'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'left',
                        'priority' => 'B2',
                    ]))
                    ->openUrlInNewTab(),
                Action::make('viewLetterLeftA4Priority')
                    ->label(__('batch.action.exportLetterLeftA4Priority'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'left',
                        'priority' => 'A',
                    ]))
                    ->openUrlInNewTab(),
                Action::make('viewLetterRightA4')
                    ->label(__('batch.action.exportLetterRightA4'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'right',
                        'priority' => 'B1',
                    ]))
                    ->openUrlInNewTab(),
                Action::make('viewLetterRightA4MassDelivery')
                    ->label(__('batch.action.exportLetterRightA4MassDelivery'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'right',
                        'priority' => 'B2',
                    ]))
                    ->openUrlInNewTab(),
                Action::make('viewLetterRightA4Priority')
                    ->label(__('batch.action.exportLetterRightA4Priority'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Model $batch) => route('batches.html', [
                        'ids' => $batch->getKey(),
                        'addressPosition' => 'right',
                        'priority' => 'A',
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->button()
            ->visible($this->record->letter_html === null)
            ->label(__('batch.action.generate_letter')),
            Action::make('viewGeneratedLetter')
                ->label(__('batch.action.view_generated_letter'))
                ->icon('heroicon-o-eye')
                ->url(fn (Model $batch) => route('batches.html', [
                    'ids' => $batch->getKey(),
                ]))
                ->openUrlInNewTab()
                ->visible($this->record->letter_html !== null),
            Action::make('setLetterHtmlNull')
                    ->label(__('batch.action.setLetterHtmlNull'))
                    ->icon('heroicon-o-trash')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') && $this->record->letter_html !== null)
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
