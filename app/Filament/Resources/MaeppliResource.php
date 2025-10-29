<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaeppliResource\Pages;
use App\Filament\Resources\MaeppliResource\RelationManagers;
use App\Models\Maeppli;
use App\Models\Sheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaeppliResource extends Resource
{
    protected static ?string $model = Maeppli::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('maeppli.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('maeppli.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->label('Bezeichnung'),
                    Forms\Components\Select::make('commune_id')
                        ->label(__('maeppli.commune'))
                        ->relationship('commune', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder(__('input.placeholder.select_commune')),
                ])->columns(2),
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('sheets_count')
                        ->label(__('maeppli.sheets_count'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->required(),
                    Forms\Components\TextInput::make('sheets_valid_count')
                        ->label(__('maeppli.sheets_valid_count'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->required(),
                    Forms\Components\TextInput::make('sheets_invalid_count')
                        ->label(__('maeppli.sheets_invalid_count'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10000)
                        ->required(),
                ])->columns(3),
            ])->columns(1);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make(__('maeppli.sections.basic_info'))
                    ->schema([
                        Components\TextEntry::make('label')
                            ->label(__('maeppli.label')),
                        Components\TextEntry::make('commune.name')
                            ->label(__('maeppli.commune')),
                        Components\TextEntry::make('created_at')
                            ->label(__('maeppli.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(3),
                Components\Section::make(__('maeppli.sections.statistics'))
                    ->schema([
                        Components\TextEntry::make('sheets_count')
                            ->label(__('maeppli.sheets_count')),
                        Components\TextEntry::make('sheets_valid_count')
                            ->label(__('maeppli.sheets_valid_count')),
                        Components\TextEntry::make('sheets_invalid_count')
                            ->label(__('maeppli.sheets_invalid_count')),
                        Components\TextEntry::make('sheets_valid_ratio')
                            ->label(__('maeppli.sheets_valid_ratio'))
                            ->getStateUsing(function ($record) {
                                $valid = (int) $record->sheets_valid_count;
                                $invalid = (int) $record->sheets_invalid_count;
                                $total = $valid + $invalid;
                                return $total > 0 ? (string)(int)($valid / $total * 100) . '%' : $valid . ' / ' . $total;
                            }),
                        Components\TextEntry::make('signatures_on_sheets')
                            ->label(__('maeppli.signatures_on_sheets'))
                            ->getStateUsing(function ($record) {
                                return $record->sheets()->sum('signatureCount');
                            }),
                        Components\TextEntry::make('signatures_total')
                            ->label(__('maeppli.signatures_total'))
                            ->getStateUsing(function ($record) {
                                return $record->sheets_valid_count+$record->sheets_invalid_count;
                            }),
                        
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('maeppli.label'))
                    ->sortable()
                    ->searchable('maepplis.label'),
                Tables\Columns\TextColumn::make('commune.name')
                    ->label(__('maeppli.commune'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('maeppli.sheets_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_valid_count')
                    ->label(__('maeppli.sheets_valid_count'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_invalid_count')
                    ->label(__('maeppli.sheets_invalid_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('maeppli.created_at'))
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('batch_created_at')
                    ->label(__('batch.created_at'))
                    ->getStateUsing(function ($record) {
                        // Get an arbitrary Sheet for this Maeppli
                        $sheet = $record->sheets()->with('batch')->first();
                        return $sheet && $sheet->batch
                            ? $sheet->batch->created_at?->format('d.m.Y')
                            : __('maeppli.no_batch');
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        // Join sheets and batches to sort by batch.created_at of the first related sheet
                        return $query->leftJoin('sheets', 'maepplis.id', '=', 'sheets.maeppli_id')
                            ->leftJoin('batches', 'sheets.batch_id', '=', 'batches.id')
                            ->orderBy('batches.created_at', $direction)
                            ->select('maepplis.*')
                            ->groupBy([
                            'maepplis.id',
                            'maepplis.label',
                            'maepplis.commune_id',
                            'maepplis.sheets_count',
                            'maepplis.sheets_valid_count',
                            'maepplis.sheets_invalid_count',
                            'maepplis.created_at',
                            'maepplis.updated_at',
                            'maepplis.deleted_at',
                        ]);
                    })                        
                    ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('sheets_valid_ratio')
                        ->label(__('maeppli.sheets_valid_ratio'))
                        ->getStateUsing(function ($record) {
                            $valid = (int) $record->sheets_valid_count;
                            $invalid = (int) $record->sheets_invalid_count;
                            $total = $valid + $invalid;
                            return $total > 0 ? (string)(int)($valid / $total * 100 ) . '%' : $valid . ' / ' . $total;
                        })
                        ->sortable(false)
                        ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signature_count_difference')
                    ->label(__('maeppli.signature_count_difference'))
                    ->getStateUsing(function ($record) {
                        $signaturesOnSheets = $record->sheets()->sum('signatureCount');
                        $signaturesTotal = $record->sheets_valid_count + $record->sheets_invalid_count;
                        return $signaturesOnSheets - $signaturesTotal;
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->leftJoin('sheets', 'maepplis.id', '=', 'sheets.maeppli_id')
                            ->selectRaw('maepplis.*, (COALESCE(SUM(sheets.signatureCount), 0) - (maepplis.sheets_valid_count + maepplis.sheets_invalid_count)) as signature_difference')
                            ->groupBy([
                                'maepplis.id',
                                'maepplis.label', 
                                'maepplis.commune_id',
                                'maepplis.sheets_count',
                                'maepplis.sheets_valid_count',
                                'maepplis.sheets_invalid_count',
                                'maepplis.created_at',
                                'maepplis.updated_at',
                                'maepplis.deleted_at',
                            ])
                            ->orderBy('signature_difference', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('signature_count_suspicious')
                    ->label(__('maeppli.filters.signature_count_suspicious'))
                    ->query(fn (Builder $query) =>
                        $query->whereHas('sheets', function (Builder $subQuery) {
                            $subQuery->selectRaw('SUM(signatureCount) as total_signatures')
                                ->groupBy('maeppli_id')
                                ->havingRaw('total_signatures > (sheets_valid_count + sheets_invalid_count)');
                        })
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SheetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaepplis::route('/'),
            'view' => Pages\ViewMaeppli::route('/{record}'),
            'edit' => Pages\EditMaeppli::route('/{record}/edit'),
        ];
    }
}
