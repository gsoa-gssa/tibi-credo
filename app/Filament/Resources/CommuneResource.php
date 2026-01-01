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
use App\Filament\Actions\ImportAddressCorrectionsAction;
use App\Filament\Actions\ScrapeAddressesAction;
use App\Filament\Actions\ScrapeAddressesBulkAction;
use App\Filament\Actions\ExportAuthorityCandidatesBulkAction;
use App\Filament\Actions\FillNameWithCantonAction;
use App\Filament\Actions\ImportBfsAction;
use Filament\Tables\Enums\FiltersLayout;

class CommuneResource extends Resource
{
    protected static ?string $model = Commune::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.geoData');
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
                Forms\Components\Section::make(__('commune.sections.details'))
                    ->schema([
                            Forms\Components\TextInput::make('name_with_canton')
                                ->label(__('commune.fields.name_with_canton'))
                                ->columnSpan(3)
                                ->disabled(),
                            Forms\Components\TextInput::make('email')
                                ->label(__('commune.fields.email'))
                                ->email()
                                ->nullable(),
                            Forms\Components\TextInput::make('website')
                                ->label(__('commune.fields.website'))
                                ->url()
                                ->nullable(),
                            Forms\Components\TextInput::make('phone')
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_name')
                                ->label(__('commune.fields.authority_address_name'))
                                ->columnSpan(3)
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_street')
                                ->label(__('commune.fields.authority_address_street'))
                                ->columnSpan(2)
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_house_number')
                                ->label(__('commune.fields.authority_address_house_number'))
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_extra')
                                ->label(__('commune.fields.authority_address_extra'))
                                ->columnSpan(3)
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_postcode')
                                ->label(__('commune.fields.authority_address_postcode'))
                                ->numeric()
                                ->minLength(4)
                                ->maxLength(4)
                                ->nullable(),
                            Forms\Components\TextInput::make('authority_address_place')
                                ->label(__('commune.fields.authority_address_place'))
                                ->columnSpan(2)
                                ->nullable(),
                            Forms\Components\Toggle::make('address_checked')
                                ->label(__('commune.fields.address_checked'))
                                ->columnSpan(3)
                                ->default(false),
                            Forms\Components\ToggleButtons::make('lang')
                                ->options([
                                    'de' => 'German',
                                    'fr' => 'French',
                                    'it' => 'Italian',
                                ])
                                ->required()
                                ->inline()
                                ->default('de'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make(__('commune.sections.setup'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('checked_on')
                            ->label(__('commune.fields.checked_on')),
                        Forms\Components\Toggle::make('dissolved')
                            ->label(__('commune.fields.dissolved'))
                            ->default(false),
                        Forms\Components\TextInput::make('officialId')
                            ->label(__('commune.fields.official_id'))
                            ->required()
                            ->numeric(),
                        Forms\Components\RichEditor::make('address')
                            ->label(__('commune.fields.address') . ' (deprecated)')
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('addressgroup')
                            ->options([
                                'none' => 'None',
                                'svegeneve' => 'Service des votations et éléctions de Genève',
                            ])
                            ->default('none')
                            ->label('Address Group (deprecated)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('canton_id')
                            ->relationship('canton', 'label')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Canton'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Forms\Components\DatePicker::make('last_contacted_on')
                    ->label(__('commune.fields.last_contacted_on'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_with_canton_and_zipcode')
                    ->label(__('commune.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('officialId')
                    ->label(__('commune.fields.official_id'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('validity_quota_current')
                    ->label(__('commune.computed.validity_quota_current'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (Model $record) {
                        $maeppli = \App\Models\Maeppli::where('commune_id', $record->id)
                            ->orderByDesc('created_at')
                            ->first();
                        if (!$maeppli) {
                            return __('commune.computed.validity_quota_current.no_data');
                        }
                        $valid = $maeppli->signatures_valid_count ?? 0;
                        $invalid = $maeppli->signatures_invalid_count ?? 0;
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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('website')
                    ->label(__('commune.fields.website'))
                    ->url(fn(Model $record) => $record->website)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signatures_in_maepplis')
                    ->label(__('commune.fields.signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->selectRaw('SUM(signatures_valid_count + signatures_invalid_count) as total')
                            ->value('total') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_signatures_in_maepplis')
                    ->label(__('commune.fields.valid_signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->sum('signatures_valid_count') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invalid_signatures_in_maepplis')
                    ->label(__('commune.fields.invalid_signatures_in_maepplis'))
                    ->numeric()
                    ->getStateUsing(function (Model $record) {
                        return \App\Models\Maeppli::where('commune_id', $record->id)
                            ->sum('signatures_invalid_count') ?? 0;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_name')
                        ->label(__('commune.fields.authority_address_name'))
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_street')
                        ->label(__('commune.fields.authority_address_street'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_house_number')
                        ->label(__('commune.fields.authority_address_house_number'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_extra')
                        ->label(__('commune.fields.authority_address_extra'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_postcode')
                        ->label(__('commune.fields.authority_address_postcode'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('authority_address_place')
                        ->label(__('commune.fields.authority_address_place'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\IconColumn::make('address_checked')
                        ->label(__('commune.fields.address_checked'))
                        ->boolean()
                        ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \App\Filament\Filters\CheckedOnFilter::make(),
                \App\Filament\Filters\BatchCreatedSinceFilter::make(),
                \App\Filament\Filters\NoBatchCreatedSinceFilter::make(),
                \App\Filament\Filters\LastContactedBeforeFilter::make(),
                \App\Filament\Filters\LastContactedAfterFilter::make(),
                Tables\Filters\Filter::make('has_zipcodes')
                    ->label(__('commune.filters.has_zipcodes'))
                    ->toggle()
                    ->default(true)
                    ->query(fn (Builder $query) => $query->whereHas('zipcodes')),
                Tables\Filters\TernaryFilter::make('address_checked')
                    ->label(__('commune.filters.address_checked'))
                    ->placeholder(__('commune.filters.address_checked.all'))
                    ->trueLabel(__('commune.filters.address_checked.true'))
                    ->falseLabel(__('commune.filters.address_checked.false'))
                    ->queries(
                        true: fn(Builder $query) => $query->where('address_checked', true),
                        false: fn(Builder $query) => $query->where('address_checked', false),
                    ),
                Tables\Filters\SelectFilter::make('lang')
                    ->label(__('commune.fields.lang'))
                    ->options([
                        'de' => __('input.label.source.label.de'),
                        'fr' => __('input.label.source.label.fr'),
                        'it' => __('input.label.source.label.it'),
                    ])
                    ->attribute('lang'),
                Tables\Filters\SelectFilter::make('canton_id')
                    ->label(__('canton.name'))
                    ->relationship('canton', 'label')
                    ->preload()
                    ->searchable(),
                    ], layout: FiltersLayout::Modal
                )->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    ImportAddressCorrectionsAction::make(),
                    ImportBfsAction::make(),
                    ImportAction::make()->importer(CommuneImporter::class),
                    ExportAction::make()->exporter(CommuneExporter::class),
                    FillNameWithCantonAction::make(),
                ])
                    ->label(__('commune.headerActionGroup.address_maintenance'))
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ScrapeAddressesBulkAction::make(),
                    ExportAuthorityCandidatesBulkAction::make(),
                    Tables\Actions\BulkAction::make('print_labels')
                        ->label('Print Labels')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $ids = $records->pluck('id')->implode(',');
                            return redirect()->route('labels.communes', ['ids' => $ids]);
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('fix_canton_suffix')
                        ->label('Fix Canton Suffix')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->fixCantonSuffix();
                                $record->save();
                            }
                            Notification::make()
                                ->title('Canton suffix fixed for selected communes.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->label(__('commune.bulkActionGroup.addressMaintenance'))
                    ->icon('heroicon-o-map-pin')
                    ->color('gray'),
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
                    ])
                        ->label(__('commune.bulkActionGroup.reminders'))
                        ->icon('heroicon-o-envelope')
                        ->color('warning'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OverviewRelationManager::class,
            RelationManagers\BatchesRelationManager::class,
            RelationManagers\MaepplisRelationManager::class,
            RelationManagers\ZipcodesRelationManager::class,
            RelationManagers\CommuneActivitylogRelationManager::class,
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
