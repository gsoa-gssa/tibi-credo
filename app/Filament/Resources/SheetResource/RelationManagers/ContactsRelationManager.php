<?php

namespace App\Filament\Resources\SheetResource\RelationManagers;

use App\Models\Zipcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('firstname')
                    ->label(__('input.label.contacts.firstname'))
                    ->required(),
                Forms\Components\TextInput::make('lastname')
                    ->label(__('input.label.contacts.lastname'))
                    ->required(),
                Forms\Components\TextInput::make('street_no')
                    ->label(__('input.label.contacts.street_no'))
                    ->required(),
                Forms\Components\DatePicker::make('birthdate')
                    ->label(__('input.label.contacts.birthdate'))
                    ->required(),
                Forms\Components\Select::make('zipcode_id')
                    ->label(__('input.label.contacts.zipcode'))
                    ->relationship('zipcode', 'code')
                    ->searchable()
                    ->searchDebounce(100)
                    ->required()
                    ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('city', Zipcode::find($state)?->nameWithCanton()))
                    ->live(),
                Forms\Components\TextInput::make('city')
                    ->label(__('input.label.contacts.city'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('firstname'),
                Tables\Columns\TextColumn::make('lastname'),
                Tables\Columns\TextColumn::make('street_no'),
                Tables\Columns\TextColumn::make('sheet.commune.name')
                    ->formatStateUsing(fn ($record) => $record->sheet->commune->nameWithCanton()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
