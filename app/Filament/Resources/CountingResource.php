<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Counting;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CountingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CountingResource\RelationManagers;

class CountingResource extends Resource
{
    protected static ?string $model = Counting::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.projectDataManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('counting.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('counting.namePlural');
    }
    
    protected static ?int $navigationSort = 0;

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Radio::make('source')
                ->options([
                    'postal' => __("resources.countings.form.source.labels.postal"),
                    'street' => __("resources.countings.form.source.labels.street"),
                    "other" => __("resources.countings.form.source.labels.other"),
                    "unknown" => __("resources.countings.form.source.labels.unknown"),
                ])
                ->default('postal')
                ->required(),
            Forms\Components\Select::make('region')
                ->options([
                    'bern' => __("resources.countings.form.region.labels.bern"),
                    'zurich' => __("resources.countings.form.region.labels.zurich"),
                    'central' => __("resources.countings.form.region.labels.central"),
                    'basel' => __("resources.countings.form.region.labels.basel"),
                    'romandie' => __("resources.countings.form.region.labels.romandie"),
                    "ticino" => __("resources.countings.form.region.labels.ticino"),
                    "diverse" => __("resources.countings.form.region.labels.diverse"),
                ])
                ->native(false),
            Forms\Components\ViewField::make('sources_warning')
                ->view('filament.forms.components.countings.sources-warning')
                ->dehydrated(false)
                ->columnSpan(2),
            Forms\Components\DateTimePicker::make('date')
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('count')
                ->required()
                ->numeric(),
            Forms\Components\RichEditor::make('description')
                ->maxLength(255)
                ->columnSpanFull()
                ->nullable()
                ->default(null),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('sumToDate')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn (Counting $counting) => $counting->trashed())
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\CountingExporter::class),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountings::route('/'),
            'create' => Pages\CreateCounting::route('/create'),
            'view' => Pages\ViewCounting::route('/{record}'),
            'edit' => Pages\EditCounting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
