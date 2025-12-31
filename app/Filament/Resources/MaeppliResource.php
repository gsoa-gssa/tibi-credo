<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaeppliResource\Pages;
use App\Models\Maeppli;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaeppliResource extends Resource
{
    protected static ?string $model = Maeppli::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.projectDataManagement');
    }

    public static function getModelLabel(): string
    {
        return __('maeppli.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maeppli.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('commune_id')
                    ->label(__('commune.name'))
                    ->relationship('commune', 'name')
                    ->columnSpan(2)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder(__('input.placeholder.select_commune')),
                Forms\Components\TextInput::make('sheets_count')
                    ->label(__('maeppli.fields.sheets_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->required(),
                Forms\Components\TextInput::make('weight_grams')
                    ->label(__('batch.fields.weight_grams'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100000)
                    ->helperText(__('batch.weight_grams_helper')),
                Forms\Components\TextInput::make('signatures_valid_count')
                    ->label(__('maeppli.fields.signatures_valid_count'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
                Forms\Components\TextInput::make('signatures_invalid_count')
                    ->label(__('maeppli.fields.signatures_invalid_count'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
            ])->columns(2);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make(__('maeppli.sections.basic_info'))
                    ->schema([
                        Components\TextEntry::make('display_label_html')
                            ->label(__('maeppli.fields.label'))
                            ->html()
                            ->getStateUsing(fn ($record) => $record->display_label_html),
                        Components\TextEntry::make('commune.name')
                            ->label(__('commune.name')),
                        Components\TextEntry::make('created_at')
                            ->label(__('maeppli.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(3),
                Components\Section::make(__('maeppli.sections.statistics'))
                    ->schema([
                        Components\TextEntry::make('sheets_count')
                            ->label(__('maeppli.fields.sheets_count')),
                        Components\TextEntry::make('weight_grams')
                            ->label(__('batch.fields.weight_grams')),
                        Components\TextEntry::make('signatures_valid_count')
                            ->label(__('maeppli.fields.signatures_valid_count')),
                        Components\TextEntry::make('signatures_invalid_count')
                            ->label(__('maeppli.fields.signatures_invalid_count')),
                        Components\TextEntry::make('sheets_valid_ratio')
                            ->label(__('maeppli.sheets_valid_ratio'))
                            ->getStateUsing(function ($record) {
                                $valid = (int) $record->signatures_valid_count;
                                $invalid = (int) $record->signatures_invalid_count;
                                $total = $valid + $invalid;
                                return $total > 0 ? (string)(int)($valid / $total * 100) . '%' : $valid . ' / ' . $total;
                            }),
                        Components\TextEntry::make('signatures_total')
                            ->label(__('maeppli.signatures_total'))
                            ->getStateUsing(function ($record) {
                                return $record->signatures_valid_count+$record->signatures_invalid_count;
                            }),
                        
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_label_html')
                    ->label(__('maeppli.fields.label'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('label_number', $direction))
                    ->searchable(['label_number'])
                    ->html()
                    ->getStateUsing(fn ($record) => $record->display_label_html),
                Tables\Columns\TextColumn::make('commune.name')
                    ->label(__('commune.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('maeppli.fields.sheets_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('signatures_valid_count')
                    ->label(__('maeppli.fields.signatures_valid_count'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signatures_invalid_count')
                    ->label(__('maeppli.fields.signatures_invalid_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('maeppli.created_at'))
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sheets_valid_ratio')
                    ->label(__('maeppli.sheets_valid_ratio'))
                    ->getStateUsing(function ($record) {
                        $valid = (int) $record->signatures_valid_count;
                        $invalid = (int) $record->signatures_invalid_count;
                        $total = $valid + $invalid;
                        return $total > 0 ? (string)(int)($valid / $total * 100 ) . '%' : $valid . ' / ' . $total;
                    })
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('valid_signatures_threshold')
                    ->label(__('maeppli.filters.valid_signatures_threshold'))
                    ->options([
                        '50' => '> 50',
                        '100' => '> 100',
                        '200' => '> 200',
                        '500' => '> 500',
                        '1000' => '> 1000',
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $data['value'] ? $query->where('signatures_valid_count', '>', (int)$data['value']) : $query
                    ),
                Tables\Filters\Filter::make('has_box')
                    ->label(__('maeppli.filters.has_box'))
                    ->query(fn (Builder $query) => $query->whereNotNull('box_id'))
                    ->toggle(),
                Tables\Filters\Filter::make('no_box')
                    ->label(__('maeppli.filters.no_box'))
                    ->query(fn (Builder $query) => $query->whereNull('box_id'))
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
            \App\Filament\Resources\MaeppliResource\RelationManagers\MaeppliActivitylogRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaepplis::route('/'),
            'create' => Pages\CreateMaeppli::route('/create'),
            'view' => Pages\ViewMaeppli::route('/{record}'),
            'edit' => Pages\EditMaeppli::route('/{record}/edit'),
        ];
    }
}
