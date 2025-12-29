<?php

namespace App\Filament\Actions\BulkActions;

use Filament\Tables\Actions\BulkActionGroup;

class ExportBatchesBulkActionGroup
{
    public static function make(): BulkActionGroup
    {
        return BulkActionGroup::make([
            ExportBatchesPdfBulkAction::make('letters_left')
                ->addressPosition('left')
                ->mailPriority('B1')
                ->label(__('batch.action.exportLetterLeftA4')),
            ExportBatchesPdfBulkAction::make('letters_left_mass_delivery')
                ->addressPosition('left')
                ->mailPriority('B2')
                ->label(__('batch.action.exportLetterLeftA4MassDelivery')),
            ExportBatchesPdfBulkAction::make('letters_left_priority')
                ->addressPosition('left')
                ->mailPriority('A')
                ->label(__('batch.action.exportLetterLeftA4Priority')),
            ExportBatchesPdfBulkAction::make('letters_right')
                ->addressPosition('right')
                ->mailPriority('B1')
                ->label(__('batch.action.exportLetterRightA4')),
            ExportBatchesPdfBulkAction::make('letters_right_mass_delivery')
                ->addressPosition('right')
                ->mailPriority('B2')
                ->label(__('batch.action.exportLetterRightA4MassDelivery')),
            ExportBatchesPdfBulkAction::make('letters_right_priority')
                ->addressPosition('right')
                ->mailPriority('A')
                ->label(__('batch.action.exportLetterRightA4Priority')),
        ])
        ->label(__('batch.action.exportLetter'))
        ->icon('heroicon-o-envelope');
    }
}
