<?php

namespace App\Filament\Actions;

use App\Models\Commune;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class SplitAuthorityNameBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'split_authority_name_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('commune.actions.split_authority_name'))
            ->icon('heroicon-o-queue-list')
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                $updated = 0;
                $skipped = 0;
                foreach ($records as $commune) {
                    if (!$commune instanceof Commune) {
                        $skipped++;
                        continue;
                    }

                    $name = trim((string)($commune->authority_address_name ?? ''));
                    $extra = trim((string)($commune->authority_address_extra ?? ''));

                    if ($name === '' || mb_strlen($name) < 31) {
                        $skipped++;
                        continue;
                    }

                    // Don't overwrite an existing extra line
                    if ($extra !== '') {
                        $skipped++;
                        continue;
                    }

                    $newName = $name;
                    $newExtra = null;

                    // some substitutions first
                    $substitutions = [
                        'Commune de ' => 'Commune politique ',
                        'Gemeinde ' => 'Politische Gemeinde ',
                    ];
                    foreach ($substitutions as $search => $replace) {
                        if (mb_stripos($name, $search) === 0) {
                            $name = $replace . mb_substr($name, mb_strlen($search));
                            break;
                        }
                    }

                    // keep common authority prefixes in the name and move the rest to extra
                    $prefixes = [
                        'Gemeindeverwaltung',
                        'Administration communale',
                        'Commune politique',
                        'Gemeindeschreiberei',
                        'Einwohnergemeinde',
                        'Einwohnerkontrolle',
                        'Politische Gemeinde',
                        'Stadtverwaltung',
                        'Bezirksverwaltung',
                        'Gemeindekanzlei',
                        'Casa comunale',
                        'Comune politico',
                        'Amministrazione comunale',
                    ];
                    foreach ($prefixes as $prefix) {
                        if (mb_stripos($name, $prefix) === 0) {
                            $newName = $prefix;
                            $newExtra = trim(preg_replace('/^[,\s]*/', '', mb_substr($name, mb_strlen($prefix))));
                            break;
                        }
                    }

                    if ($newExtra === null || $newExtra === '') {
                        $skipped++;
                        continue;
                    }

                    $commune->authority_address_name = $newName;
                    $commune->authority_address_extra = $newExtra;
                    $commune->save();
                    $updated++;
                }

                if ($updated > 0) {
                    Notification::make()->success()
                        ->title(__('commune.actions.split_authority_name_complete'))
                        ->body(__('commune.actions.split_authority_name_stats', ['count' => $updated, 'skipped' => $skipped]))
                        ->send();
                } else {
                    Notification::make()->info()
                        ->title(__('commune.actions.split_authority_name_no_changes'))
                        ->body(__('commune.actions.split_authority_name_no_changes_body'))
                        ->send();
                }
            });
    }
}
