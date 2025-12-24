<?php

namespace App\Filament\Resources\CommuneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ZipcodesRelationManager extends RelationManager
{
    protected static string $relationship = 'zipcodes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('zipcode.namePlural');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('zipcode.fields.code'))
                    ->required()
                    ->maxLength(4)
                    ->numeric(),
                Forms\Components\TextInput::make('name')
                    ->label(__('zipcode.fields.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('zipcode.fields.code'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('zipcode.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('number_of_dwellings')
                    ->label(__('zipcode.fields.number_of_dwellings'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Remove create action for view-only
            ])
            ->actions([
                // Remove edit and delete actions for view-only
            ])
            ->bulkActions([
                // Remove bulk actions for view-only
            ]);
    }
}