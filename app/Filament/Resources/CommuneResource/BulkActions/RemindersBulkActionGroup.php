<?php

namespace App\Filament\Resources\CommuneResource\BulkActions;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;
use App\Models\Commune;
use Illuminate\Support\Collection;

class RemindersBulkActionGroup
{
    public static function make(): BulkActionGroup
    {
        return BulkActionGroup::make([
            BulkAction::make('export_only')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (Collection $records) {
                    $csv = fopen('php://temp', 'r+');
                    fputcsv($csv, [
                        'ID',
                        'Name',
                        'Email',
                        'Phone',
                        'Address',
                        'Canton',
                        'Last Contacted On',
                    ]);
                    foreach ($records as $record) {
                        fputcsv($csv, [
                            $record->id,
                            $record->name,
                            $record->email,
                            $record->phone,
                            $record->address,
                            optional($record->canton)->label,
                            optional($record->last_contacted_on)?->toDateString(),
                        ]);
                    }
                    rewind($csv);
                    return response()->streamDownload(function () use ($csv) {
                        fpassthru($csv);
                    }, 'communes-export-' . now()->format('Ymd_His') . '.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
            }),
            BulkAction::make('export_and_mark_contacted')
                ->label('Export & mark contacted')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    $now = now();

                    $csv = fopen('php://temp', 'r+');
                    fputcsv($csv, [
                        'ID',
                        'Name',
                        'Email',
                        'Phone',
                        'Address',
                        'Canton',
                        'Last Contacted On',
                    ]);

                    foreach ($records as $record) {
                        $record->update(['last_contacted_on' => $now]);

                        fputcsv($csv, [
                            $record->id,
                            $record->name,
                            $record->email,
                            $record->phone,
                            $record->address,
                            optional($record->canton)->label,
                            $now->toDateString(),
                        ]);
                    }

                    rewind($csv);

                    return response()->streamDownload(function () use ($csv) {
                        fpassthru($csv);
                    }, 'communes-reminder-' . now()->format('Ymd_His') . '.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            BulkAction::make('clear_last_contacted')
                ->label('Clear Last Contacted')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    foreach ($records as $record) {
                        $record->update(['last_contacted_on' => null]);
                    }
                    Notification::make()
                        ->title('Last contacted date cleared for ' . $records->count() . ' commune(s).')
                        ->success()
                        ->send();
                }),
            BulkAction::make('reset_last_contacted_to_previous')
                ->label('Reset Last Contacted to Previous')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    $updated = 0;
                    foreach ($records as $record) {
                        $activity = Activity::where('subject_type', Commune::class)
                            ->where('subject_id', $record->id)
                            ->orderByDesc('created_at')
                            ->get()
                            ->first(function ($act) {
                                $changes = $act->changes();
                                return $changes && isset($changes['old']) && array_key_exists('last_contacted_on', $changes['old']);
                            });
                        
                        if ($activity) {
                            $previousValue = $activity->changes()['old']['last_contacted_on'];
                            $record->update(['last_contacted_on' => $previousValue]);
                            $updated++;
                        }
                    }
                    Notification::make()
                        ->title('Last contacted date reset to previous values for ' . $updated . ' commune(s).')
                        ->success()
                        ->send();
                }),
        ])
            ->label(__('commune.bulkActionGroup.reminders'))
            ->icon('heroicon-o-envelope')
            ->color('warning');
    }
}
