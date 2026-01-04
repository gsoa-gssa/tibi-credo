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
            ->modalHeading('')
            ->modalSubmitAction(false)
            ->modalWidth('2xl')
            ->modalContent(fn (?Collection $records) => view('filament.modals.batch-letters-modal', [
                'records' => $records,
            ]))
            ->label(__('batch.action.generate_letters'))
            ->icon('heroicon-o-link');
    }
}
