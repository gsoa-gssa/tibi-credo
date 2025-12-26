<?php

namespace App\Filament\Resources;

use App\Models\SignatureCollection;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class SignatureCollectionResource extends Resource
{
    protected static ?string $model = SignatureCollection::class;
    protected static ?string $navigationIcon = 'heroicon-o-document';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('signature_collection.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('signature_collection.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('short_name')->required(),
            Forms\Components\Tabs::make('Languages')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Deutsch')
                        ->schema([
                            TextInput::make('official_name_de')->required(),
                            TextInput::make('responsible_person_name_de')->required(),
                            TextInput::make('responsible_person_email_de')->email()->required(),
                            TextInput::make('responsible_person_phone_de')->required(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Français')
                        ->schema([
                            TextInput::make('official_name_fr')->required(),
                            TextInput::make('responsible_person_name_fr')->required(),
                            TextInput::make('responsible_person_email_fr')->email()->required(),
                            TextInput::make('responsible_person_phone_fr')->required(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Italiano')
                        ->schema([
                            TextInput::make('official_name_it')->required(),
                            TextInput::make('responsible_person_name_it')->required(),
                            TextInput::make('responsible_person_email_it')->email()->required(),
                            TextInput::make('responsible_person_phone_it')->required(),
                        ]),
                ]),
            DatePicker::make('publication_date'),
            DatePicker::make('end_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_name'),
                TextColumn::make('official_name_de')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_name_fr')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_name_it')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('publication_date')->date(),
                TextColumn::make('end_date')->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SignatureCollectionResource\Pages\ListSignatureCollections::route('/'),
            'create' => SignatureCollectionResource\Pages\CreateSignatureCollection::route('/create'),
            'edit' => SignatureCollectionResource\Pages\EditSignatureCollection::route('/{record}/edit'),
        ];
    }
}
