<?php

namespace App\Filament\Actions\BulkActions;

use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ShowBatchLettersBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('show_letters')
            ->modal()
            ->modalHeading(__('batch.notification.choose_letter_variant'))
            ->modalDescription(__('batch.notification.choose_letter_variant_body'))
            ->modalWidth('2xl')
            ->modalContent(fn (?Collection $records) => view('filament.modals.batch-letters-modal', [
                'records' => $records,
            ]))
            ->label(__('batch.action.showLetter'))
            ->icon('heroicon-o-link');
    }
}
