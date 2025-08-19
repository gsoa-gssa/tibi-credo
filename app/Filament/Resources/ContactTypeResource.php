<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactTypeResource\Pages;
use App\Filament\Resources\ContactTypeResource\RelationManagers;
use App\Models\ContactType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactTypeResource extends Resource
{
    protected static ?string $model = ContactType::class;
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(8),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Section::make('Subject')
                    ->schema([
                        Forms\Components\TextInput::make('subject_de')
                            ->required()
                            ->maxLength(80),
                        Forms\Components\TextInput::make('subject_fr')
                            ->required()
                            ->maxLength(80),
                        Forms\Components\TextInput::make('subject_it')
                            ->required()
                            ->maxLength(80),
                    ]),
                Forms\Components\Section::make('Body')
                    ->schema([
                        Forms\Components\TextInput::make('body_de')
                            ->required()
                            ->maxLength(800),
                        Forms\Components\TextInput::make('body_fr')
                            ->required()
                            ->maxLength(800),
                        Forms\Components\TextInput::make('body_it')
                            ->required()
                            ->maxLength(800),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('description')->toggleable(),
                Tables\Columns\TextColumn::make('subject_de')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_fr')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_it')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_de')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_fr')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_it')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(50)
            ->filters([
                //
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactTypes::route('/'),
            'create' => Pages\CreateContactType::route('/create'),
            'edit' => Pages\EditContactType::route('/{record}/edit'),
            'view' => Pages\ViewContactType::route('/{record}'),
        ];
    }
}
