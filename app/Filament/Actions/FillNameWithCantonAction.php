<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Commune;

class FillNameWithCantonAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'fillNameWithCanton';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('commune.actions.fill_name_with_canton'))
            ->icon('heroicon-o-pencil')
            ->requiresConfirmation()
            ->action(function () {
                try {
                    $communes = Commune::where(function ($query) {
                        $query->whereNull('name_with_canton')
                            ->orWhere('name_with_canton', '');
                    })
                        ->with('canton')
                        ->get();

                    $updated = 0;
                    $errors = 0;

                    foreach ($communes as $commune) {
                        try {
                            if (!$commune->canton) {
                                $errors++;
                                continue;
                            }
                            $commune->update([
                                'name_with_canton' => $commune->nameWithCanton(),
                            ]);
                            $updated++;
                        } catch (\Exception $e) {
                            $errors++;
                        }
                    }

                    if ($updated > 0) {
                        Notification::make()
                            ->success()
                            ->title(__('commune.actions.fill_name_with_canton_complete'))
                            ->body(__('commune.actions.fill_name_with_canton_stats', ['count' => $updated]))
                            ->send();
                    } elseif ($errors > 0) {
                        Notification::make()
                            ->warning()
                            ->title('Fehler')
                            ->body("$errors communes haben keinen Kanton zugewiesen")
                            ->send();
                    } else {
                        Notification::make()
                            ->info()
                            ->title('Keine Änderungen')
                            ->body('Alle Communes haben bereits einen Namen mit Kanton')
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Fehler beim Füllen')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
