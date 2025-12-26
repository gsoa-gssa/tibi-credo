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
            Forms\Components\TextInput::make('count')
                ->label(__('counting.fields.count'))
                ->required()
                ->live(onBlur: true)
                ->numeric()
                ->extraAttributes([
                    'data-count-input' => 'true',
                ])
                ->columnSpan(2),
            Forms\Components\Checkbox::make('confirm_large_count')
                ->label(__('counting.fields.confirmLargeCount'))
                ->default(false)
                ->required(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) > 100)
                ->hidden(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) <= 100)
                ->dehydrated(false)
                ->extraAttributes([
                    'id' => 'confirm-large-count-checkbox',
                    'data-large-count-field' => 'true',
                ])
                ->columnSpan(2),
            Forms\Components\Select::make('source_id')
                ->label(__('source.name'))
                ->relationship('source', 'code')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\ToggleButtons::make('paper_format')
                ->label(__('counting.fields.paper_format'))
                ->options([
                    false => 'A4',
                    true => 'A5',
                ])
                ->default(false)
                ->inline(),
            Forms\Components\DatePicker::make('date')
                ->label(__('counting.fields.date'))
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label(__('counting.fields.name'))
                ->required()
                ->maxLength(255),

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
                    ->date()
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
