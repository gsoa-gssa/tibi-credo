<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Exports\ContactExporter;
use Filament\Tables\Actions\ExportAction;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    protected static ?int $navigationSort = 4;

    // Add model label
    public static function getModelLabel(): string
    {
        return __('contact.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('contact.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('firstname')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('lastname')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('street_no')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('zipcode_id')
                    ->label(__('zipcode.name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} {$record->name}")
                    ->relationship('zipcode', 'name')
                    ->required()
                    ->searchable(['code', 'name'])
                    ->preload(),
                Forms\Components\DatePicker::make('birthdate')
                    ->required(),
                Forms\Components\Select::make('sheet_id')
                    ->relationship('sheet', 'label')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('firstname')
                    ->label(__('contact.fields.firstname')),
                Infolists\Components\TextEntry::make('lastname')
                    ->label(__('contact.fields.lastname')),
                Infolists\Components\TextEntry::make('birthdate')
                    ->label(__('contact.fields.birthdate'))
                    ->date(),
                Infolists\Components\TextEntry::make('street_no')
                    ->label(__('contact.fields.street_no')),
                Infolists\Components\TextEntry::make('zipcode.code')
                    ->label(__('zipcode.fields.code')),
                Infolists\Components\TextEntry::make('zipcode.name')
                    ->label(__('zipcode.fields.name')),
                Infolists\Components\TextEntry::make('zipcode.commune.lang')
                    ->label(__('commune.fields.lang')),
                Infolists\Components\TextEntry::make('sheet.label')
                    ->label(__('sheet.name'))
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->url(function (Contact $contact) {
                        if ($contact->sheet) {
                            return SheetResource::getUrl("view", ["record" => $contact->sheet]);
                        }
                        return null;
                    })
                    ->color('primary'),
            ])
            ->columns(3);
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
                Tables\Columns\TextColumn::make('firstname')
                    ->label(__('contact.fields.firstname'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label(__('contact.fields.lastname'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('street_no')
                    ->label(__('contact.fields.street_no'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('zipcode.code')
                    ->label(__('zipcode.fields.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('zipcode.name')
                    ->label(__('zipcode.fields.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('zipcode.commune.lang')
                    ->label(__('commune.fields.lang'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('birthdate')
                    ->label(__('contact.fields.birthdate'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sheet.label')
                    ->label(__('sheet.name'))
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->url(function (Contact $contact) {
                        if ($contact->sheet) {
                            return SheetResource::getUrl("view", ["record" => $contact->sheet]);
                        } else {
                            return null;
                        }
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ContactExporter::class),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
