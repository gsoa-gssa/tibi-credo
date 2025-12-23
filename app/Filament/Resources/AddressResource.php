<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Models\Address;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Data Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->required(),
                Select::make('zipcode_id')
                    ->relationship('zipcode', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('street_name')
                    ->maxLength(120),
                TextInput::make('street_number')
                    ->maxLength(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('commune.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('zipcode.code')
                    ->label('Zipcode')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('zipcode.name')
                    ->label('Place')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('street_name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('street_number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('commune')
                    ->relationship('commune', 'name')
                    ->searchable(),
                SelectFilter::make('zipcode')
                    ->relationship('zipcode', 'name')
                    ->searchable(),
            ])
            ->actions([
                // No edit/delete for now since these are imported from BFS
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
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
            'index' => Pages\ListAddresses::route('/'),
            'view' => Pages\ViewAddress::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Address');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Addresses');
    }
}
