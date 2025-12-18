<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Commune;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use App\Filament\Exports\CommuneExporter;
use App\Filament\Imports\CommuneImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CommuneResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CommuneResource\RelationManagers;
use Spatie\Activitylog\Models\Activity;

class CommuneResource extends Resource
{
    protected static ?string $model = Commune::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('commune.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commune.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('officialId')
                    ->label(__('commune.fields.official_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\RichEditor::make('address')
                    ->nullable(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->nullable(),
                Forms\Components\TextInput::make('website')
                    ->url()
                    ->nullable(),
                Forms\Components\TextInput::make('phone')
                    ->nullable(),
                Forms\Components\Select::make('addressgroup')
                    ->options([
                        'none' => 'None',
                        'svegeneve' => 'Service des votations et éléctions de Genève',
                    ])
                    ->default('none')
                    ->label('Address Group')
                    ->required(),
                Forms\Components\Select::make('canton_id')
                    ->relationship('canton', 'label')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Canton'),
                Forms\Components\ToggleButtons::make('lang')
                    ->options([
                        'de' => 'German',
                        'fr' => 'French',
                        'it' => 'Italian',
                    ])
                    ->required()
                    ->inline()
                    ->default('de'),
                Forms\Components\DatePicker::make('last_contacted_on')
                    ->label(__('commune.fields.last_contacted_on'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('officialId')
                    ->label(__('commune.fields.official_id'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_no_batch')
                    ->sortable(query: function (Builder $query, $direction) {
                        $query->withCount(['sheets as sheets_count' => function($query) {
                            $query->where("status", "recorded");
                        }])->orderBy("sheets_count", $direction);
                    })
                    ->label(__('commune.computed.sheets_no_batch'))
                    ->getStateUsing(function (Model $record) {
                        return $record->sheets()->where("status", "recorded")->count();
                    }),
                Tables\Columns\TextColumn::make('validity_quota_current')
                    ->label(__('commune.computed.validity_quota_current'))
                    ->getStateUsing(function (Model $record) {
                        $maeppli = \App\Models\Maeppli::where('commune_id', $record->id)
                            ->orderByDesc('created_at')
                            ->first();
                        if (!$maeppli) {
                            return __('commune.computed.validity_quota_current.no_data');
                        }
                        $valid = $maeppli->sheets_valid_count ?? 0;
                        $invalid = $maeppli->sheets_invalid_count ?? 0;
                        $total = $valid + $invalid;
                        if ($total === 0) {
                            return "Leerer Versand?";
                        }
                        return __('commune.computed.validity_quota_current_data', [
                            'percent' => round(($valid / $total) * 100) . '%',
                            'total' => $total
                        ]);
                    }),
                Tables\Columns\TextColumn::make('last_batch_created_at')
                    ->label(__('commune.computed.last_batch_created_at'))
                    ->dateTime()
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->addSelect([
                            'last_batch_created_at' => \App\Models\Batch::select('created_at')
                                ->whereColumn('commune_id', 'communes.id')
                                ->latest('created_at')
                                ->limit(1)
                        ])->orderBy('last_batch_created_at', $direction);
                    })
                    ->getStateUsing(function (Model $record) {
                        $lastBatch = $record->batches()->latest('created_at')->first();
                        return $lastBatch ? $lastBatch->created_at : null;
                        })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_contacted_on')
                    ->label(__('commune.fields.last_contacted_on'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sheets_sent_not_returned')
                    ->label(__('commune.fields.sheets_sent_not_returned'))
                    ->numeric()
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->withCount(['sheets as sheets_sent_not_returned_count' => function ($q) {
                            $q->whereNotNull('batch_id')
                              ->whereNull('maeppli_id')
                              ->whereHas('batch', function ($bq) {
                                  $bq->whereNull('deleted_at')
                                     ->whereColumn('commune_id', 'communes.id');
                              });
                        }])->orderBy('sheets_sent_not_returned_count', $direction);
                    })
                    ->getStateUsing(fn (Model $record) => $record->sheets()
                        ->whereNotNull('batch_id')
                        ->whereNull('maeppli_id')
                        ->whereHas('batch', function ($bq) use ($record) {
                            $bq->whereNull('deleted_at')
                               ->where('commune_id', $record->id);
                        })
                        ->count())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sheets_sent_not_returned_percent')
                    ->label(__('commune.fields.sheets_sent_not_returned_percent'))
                    ->getStateUsing(function (Model $record) {
                        $sent = $record->sheets()->whereNotNull('batch_id')->count();
                        if ($sent === 0) return 'N/A';
                        $notReturned = $record->sheets()->whereNotNull('batch_id')->whereNull('maeppli_id')->count();
                        return round(($notReturned / $sent) * 100, 1) . '%';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signatures_on_sheets')
                    ->label(__('commune.fields.signatures_on_sheets'))
                    ->numeric()
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->withSum('sheets as total_signatures', 'signatureCount')
                            ->orderBy('total_signatures', $direction);
                    })
                    ->getStateUsing(fn (Model $record) => $record->sheets()->sum('signatureCount'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signatures_in_batches')
                    ->label(__('commune.fields.signatures_in_batches'))
                    ->numeric()
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->withSum(['sheets as batch_signatures' => fn($q) => $q->whereNotNull('batch_id')], 'signatureCount')
                            ->orderBy('batch_signatures', $direction);
                    })
                    ->getStateUsing(fn (Model $record) => $record->sheets()->whereNotNull('batch_id')->sum('signatureCount'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signatures_in_maepplis')
                    ->label(__('commune.fields.signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->selectRaw('SUM(sheets_valid_count + sheets_invalid_count) as total')
                            ->value('total') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_signatures_in_maepplis')
                    ->label(__('commune.fields.valid_signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->sum('sheets_valid_count') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invalid_signatures_in_maepplis')
                    ->label(__('commune.fields.invalid_signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->sum('sheets_invalid_count') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch_created_since')
                    ->label(__('commune.filters.batch_created_since'))
                    ->options([
                        'today' => __('commune.filters.batch_created_since.today'),
                        'since_yesterday' => __('commune.filters.batch_created_since.since_yesterday'),
                        'since_1_week' => __('commune.filters.batch_created_since.since_1_week'),
                        'since_1_month' => __('commune.filters.batch_created_since.since_1_month'),
                        'since_3_months' => __('commune.filters.batch_created_since.since_3_months'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        $date = match($data['value']) {
                            'today' => now()->startOfDay(),
                            'since_yesterday' => now()->subDay()->startOfDay(),
                            'since_1_week' => now()->subWeek()->startOfDay(),
                            'since_1_month' => now()->subMonth()->startOfDay(),
                            'since_3_months' => now()->subMonths(3)->startOfDay(),
                            default => null,
                        };
                        if (!$date) {
                            return $query;
                        }
                        return $query->whereHas('batches', function ($q) use ($date) {
                            $q->whereNull('deleted_at')->where('created_at', '>=', $date);
                        });
                    }),
                Tables\Filters\SelectFilter::make('no_batch_created_since')
                    ->label(__('commune.filters.no_batch_created_since'))
                    ->options([
                        'today' => __('commune.filters.no_batch_created_since.today'),
                        'since_yesterday' => __('commune.filters.no_batch_created_since.since_yesterday'),
                        'since_1_week' => __('commune.filters.no_batch_created_since.since_1_week'),
                        'since_1_month' => __('commune.filters.no_batch_created_since.since_1_month'),
                        'since_3_months' => __('commune.filters.no_batch_created_since.since_3_months'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        $date = match($data['value']) {
                            'today' => now()->startOfDay(),
                            'since_yesterday' => now()->subDay()->startOfDay(),
                            'since_1_week' => now()->subWeek()->startOfDay(),
                            'since_1_month' => now()->subMonth()->startOfDay(),
                            'since_3_months' => now()->subMonths(3)->startOfDay(),
                            default => null,
                        };
                        if (!$date) {
                            return $query;
                        }
                        return $query->whereDoesntHave('batches', function ($q) use ($date) {
                            $q->whereNull('deleted_at')->where('created_at', '>=', $date);
                        });
                    }),
                Tables\Filters\SelectFilter::make('last_contacted_before')
                    ->label(__('commune.filters.last_contacted_on_before'))
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        '2_days' => '2 days ago',
                        '3_days' => '3 days ago',
                        '4_days' => '4 days ago',
                        '5_days' => '5 days ago',
                        '1_week' => '1 week ago',
                        '2_weeks' => '2 weeks ago',
                        '1_month' => '1 month ago',
                        'more_than_1_month' => 'More than 1 month ago',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $date = match($data['value']) {
                            'today' => now()->startOfDay(),
                            'yesterday' => now()->subDay()->startOfDay(),
                            '2_days' => now()->subDays(2)->startOfDay(),
                            '3_days' => now()->subDays(3)->startOfDay(),
                            '4_days' => now()->subDays(4)->startOfDay(),
                            '5_days' => now()->subDays(5)->startOfDay(),
                            '1_week' => now()->subWeek()->startOfDay(),
                            '2_weeks' => now()->subWeeks(2)->startOfDay(),
                            '1_month' => now()->subMonth()->startOfDay(),
                            'more_than_1_month' => now()->subMonth()->startOfDay(),
                            default => null,
                        };

                        if (!$date) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($date) {
                            $q->where('last_contacted_on', '<', $date)
                              ->orWhereNull('last_contacted_on');
                        });
                    }),
                Tables\Filters\SelectFilter::make('last_contacted_after')
                    ->label(__('commune.filters.last_contacted_on_after'))
                    ->options([
                        'yesterday' => 'Yesterday',
                        '2_days' => '2 days ago',
                        '3_days' => '3 days ago',
                        '4_days' => '4 days ago',
                        '5_days' => '5 days ago',
                        '1_week' => '1 week ago',
                        '2_weeks' => '2 weeks ago',
                        '1_month' => '1 month ago',
                        'more_than_1_month' => 'More than 1 month ago',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $date = match($data['value']) {
                            'today' => now()->startOfDay(),
                            'yesterday' => now()->subDay()->startOfDay(),
                            '2_days' => now()->subDays(2)->startOfDay(),
                            '3_days' => now()->subDays(3)->startOfDay(),
                            '4_days' => now()->subDays(4)->startOfDay(),
                            '5_days' => now()->subDays(5)->startOfDay(),
                            '1_week' => now()->subWeek()->startOfDay(),
                            '2_weeks' => now()->subWeeks(2)->startOfDay(),
                            '1_month' => now()->subMonth()->startOfDay(),
                            'more_than_1_month' => now()->subMonth()->startOfDay(),
                            default => null,
                        };

                        if (!$date) {
                            return $query;
                        }

                        return $query->where('last_contacted_on', '>', $date);
                    }),
                Tables\Filters\Filter::make('sheets_sent_not_returned')
                    ->label(__('commune.filters.sheets_sent_not_returned'))
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->numeric()
                            ->label('Minimum Bögen nicht retourniert'),
                        Forms\Components\TextInput::make('max')
                            ->numeric()
                            ->label('Maximum Bögen nicht retourniert'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $min = $data['min'] ?? null;
                        $max = $data['max'] ?? null;

                        // Only apply filter if at least one value is set
                        if ($min === null && $max === null) {
                            return $query;
                        }

                        // Ensure commune has sent at least one non-deleted batch
                        $query->whereHas('sheets', function ($q) {
                            $q->whereNotNull('batch_id')
                              ->whereHas('batch', function ($bq) {
                                  // Exclude soft-deleted batches and ensure batch belongs to this commune
                                  $bq->whereNull('deleted_at')
                                     ->whereColumn('commune_id', 'communes.id');
                              });
                        });

                        return $query
                            ->when($min !== null && $min !== '', function (Builder $q) use ($min) {
                                $q->whereHas('sheets', function ($q) {
                                    $q->whereNotNull('batch_id')
                                      ->whereNull('maeppli_id')
                                      ->whereHas('batch', function ($bq) {
                                          $bq->whereNull('deleted_at')
                                             ->whereColumn('commune_id', 'communes.id');
                                      });
                                }, '>=', (int) $min);
                            })
                            ->when($max !== null && $max !== '', function (Builder $q) use ($max) {
                                $q->whereHas('sheets', function ($q) {
                                    $q->whereNotNull('batch_id')
                                      ->whereNull('maeppli_id')
                                      ->whereHas('batch', function ($bq) {
                                          $bq->whereNull('deleted_at')
                                             ->whereColumn('commune_id', 'communes.id');
                                      });
                                }, '<=', (int) $max);
                            });
                    }),
            //     Tables\Filters\Filter::make('sheets_not_returned_percent')
            //         ->label(__('commune.filters.sheets_not_returned_percent'))
            //         ->form([
            //             Forms\Components\TextInput::make('min')
            //                 ->numeric()
            //                 ->suffix('%')
            //                 ->label('Minimum % Bögen nicht retourniert'),
            //             Forms\Components\TextInput::make('max')
            //                 ->numeric()
            //                 ->suffix('%')
            //                 ->label('Maximum % Bögen nicht retourniert'),
            //         ])
            //         ->query(function (Builder $query, array $data): Builder {
            //             $min = isset($data['min']) && $data['min'] !== '' ? (float) $data['min'] : null;
            //             $max = isset($data['max']) && $data['max'] !== '' ? (float) $data['max'] : null;

            //             if ($min === null && $max === null) {
            //                 return $query;
            //             }

            //             $notReturnedExpr = '(SELECT COUNT(*) FROM sheets WHERE sheets.commune_id = communes.id AND sheets.batch_id IS NOT NULL AND sheets.maeppli_id IS NULL)';
            //             $sentExpr = '(SELECT COUNT(*) FROM sheets WHERE sheets.commune_id = communes.id AND sheets.batch_id IS NOT NULL)';

            //             // Only consider communes that have sent sheets.
            //             $query->whereRaw("{$sentExpr} > 0");

            //             if ($min !== null) {
            //                 $query->whereRaw("{$notReturnedExpr} * 100.0 / {$sentExpr} >= ?", [$min]);
            //             }

            //             if ($max !== null) {
            //                 $query->whereRaw("{$notReturnedExpr} * 100.0 / {$sentExpr} <= ?", [$max]);
            //             }

            //             return $query;
            //         }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                ImportAction::make()->importer(CommuneImporter::class),
                ExportAction::make()->exporter(CommuneExporter::class),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_only')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (\Illuminate\Support\Collection $records) {
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
                    Tables\Actions\BulkAction::make('export_and_mark_contacted')
                        ->label('Export & mark contacted')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
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
                                // Update last contacted date
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
                    Tables\Actions\BulkAction::make('fix_canton_suffix')
                        ->label('Fix Canton Suffix')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->fixCantonSuffix();
                            }
                            Notification::make()
                                ->title('Canton suffix fixed for selected communes.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('clear_last_contacted')
                        ->label('Clear Last Contacted')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['last_contacted_on' => null]);
                            }
                            Notification::make()
                                ->title('Last contacted date cleared for ' . $records->count() . ' commune(s).')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('reset_last_contacted_to_previous')
                        ->label('Reset Last Contacted to Previous')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                // Find the most recent activity that changed last_contacted_on
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BatchesRelationManager::class,
            RelationManagers\MaepplisRelationManager::class,
            RelationManagers\ZipcodesRelationManager::class,
            RelationManagers\CommuneActivitylogRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CommuneResource\Widgets\CommuneStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunes::route('/'),
            'create' => Pages\CreateCommune::route('/create'),
            'edit' => Pages\EditCommune::route('/{record}/edit'),
            'view' => Pages\ViewCommune::route('/{record}'),
        ];
    }
}
