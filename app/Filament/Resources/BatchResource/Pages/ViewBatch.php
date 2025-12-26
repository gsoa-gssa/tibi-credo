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
                        $batch->mark_standard_delivery();
                        return $batch->pdf(true, false);
                    }),
                Action::make("exportLetterLeftA4")
                    ->label(__('batch.action.exportLetterLeftA4MassDelivery'))
                    ->icon("heroicon-o-envelope")
                    ->action(function (Model $batch){
                        $batch->mark_mass_delivery();
                        return $batch->pdf(true, false);
                    }),
                Action::make("exportLetterLeftA4Priority")
                    ->label(__('batch.action.exportLetterLeftA4Priority'))
                    ->icon("heroicon-o-envelope")
                    ->action(function (Model $batch){
                        $batch->mark_priority_delivery();
                        return $batch->pdf(true, true);
                    }),
                Action::make('exportLetterRightA4')
                    ->label(__('batch.action.exportLetterRightA4'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->mark_standard_delivery();
                        return $batch->pdf(false);
                    }),
                Action::make('exportLetterRightA4')
                    ->label(__('batch.action.exportLetterRightA4MassDelivery'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->mark_mass_delivery();
                        return $batch->pdf(false);
                    }),
                Action::make('exportLetterRightA4Priority')
                    ->label(__('batch.action.exportLetterRightA4Priority'))
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $batch){
                        $batch->mark_priority_delivery();
                        return $batch->pdf(false, true);
                    }),
            ])
            ->button()
            ->label(__('batch.action.exportLetter')),
            Action::make('edit')
                ->label(__('batch.action.editBatch'))
                ->icon('heroicon-o-pencil')
                ->url(fn () => route('filament.app.resources.batches.edit', $this->record)),
            
        ];
    }
}
