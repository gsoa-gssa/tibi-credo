<?php

namespace App\Filament\Resources\CommuneResource\BulkActions;

use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use App\Models\Commune;
use Illuminate\Support\Collection;

class RemindersBulkActionGroup
{
    /**
     * Return a single bulk action that opens a modal allowing the user
     * to choose between exporting only or exporting and marking contacted.
     */
    public static function make(): BulkAction
    {
        return BulkAction::make('reminders')
            ->label(__('commune.bulkActions.reminders.label'))
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->modalHeading(__('commune.bulkActions.reminders.label'))
            ->modalDescription(__('commune.bulkActions.reminders.description'))
            ->form([
                Radio::make('choice')
                    ->label('')
                    ->options([
                        'export_only' => __('commune.bulkActions.reminders.export'),
                        'export_and_mark_contacted' => __('commune.bulkActions.reminders.exportAndComment'),
                        'comment_only' => __('commune.bulkActions.reminders.commentOnly'),
                    ])
                    ->required()
                    ->live()
                    ->inline(),
                TextInput::make('comment')
                    ->label(__('commune.bulkActions.reminders.comment'))
                    ->visible(fn ($get) => $get('choice') === 'comment_only')
                    ->required(fn ($get) => $get('choice') === 'comment_only')
            ])
            ->action(function (Collection $records, array $data = []) {
                $choice = $data['choice'];
                if ($choice === 'comment_only') {
                    foreach ($records as $record) {
                        $record->addComment($data['comment']);
                    }
                    return;
                } else if (in_array($choice, ['export_only', 'export_and_mark_contacted'])) {
                    if ($choice === 'export_and_mark_contacted') {
                        foreach ($records as $record) {
                            $record->addComment(__('commune.bulkActions.reminders.markedAsContacted'));
                        }
                    }
                    return Commune::streamRemindersCSV($records);
                } else {
                    throw new \Exception('Invalid choice for reminders bulk action: ' . $choice);
                }
            });
    }
}
