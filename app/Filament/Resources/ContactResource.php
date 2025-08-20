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
use App\Filament\Imports\ContactImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Actions\BulkActions\ExportContactsPdfBulkAction;

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
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $zipcode = \App\Models\Zipcode::find($state);
                            if ($zipcode && $zipcode->commune) {
                                $set('lang', $zipcode->commune->lang);
                            }
                        }
                    }),
                Forms\Components\DatePicker::make('birthdate')
                    ->required(),
                Forms\Components\ToggleButtons::make('lang')
                    ->options([
                        'de' => 'German',
                        'fr' => 'French',
                        'it' => 'Italian',
                    ])
                    ->required()
                    ->inline()
                    ->afterStateHydrated(function (Forms\Components\ToggleButtons $component, $state, $record) {
                        if ($record && $record->lang) {
                            return;
                        }
        
                        if ($record && $record->zipcode && $record->zipcode->commune) {
                            $component->state($record->zipcode->commune->lang);
                        }
                    }),
                Forms\Components\Select::make('sheet_id')
                    ->relationship('sheet', 'label')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('contact_type_id')
                    ->relationship('contactType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DateTimePicker::make('letter_sent')
                    ->label(__('contact.fields.letter_sent')),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('contact.sections.personal_info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('firstname')
                            ->label(__('contact.fields.firstname')),
                        Infolists\Components\TextEntry::make('lastname')
                            ->label(__('contact.fields.lastname')),
                        Infolists\Components\TextEntry::make('birthdate')
                            ->label(__('contact.fields.birthdate'))
                            ->date(),
                        Infolists\Components\TextEntry::make('lang')
                            ->label(__('commune.fields.lang')),
                    ])
                    ->columns(4),
                InfoLists\Components\Section::make(__('contact.sections.address'))
                    ->schema([
                        Infolists\Components\TextEntry::make('street_no')
                            ->label(__('contact.fields.street_no')),
                        Infolists\Components\TextEntry::make('zipcode.code')
                            ->label(__('zipcode.fields.code')),
                        Infolists\Components\TextEntry::make('zipcode.name')
                            ->label(__('zipcode.fields.name')),
                    ])
                    ->columns(3),
                InfoLists\Components\Section::make(__('contact.sections.details'))
                    ->schema([
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
                        Infolists\Components\TextEntry::make('contactType.name')
                            ->label(__('contact.fields.contact_type'))
                            ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                            ->url(function (Contact $contact) {
                                if ($contact->contactType) {
                                    return ContactTypeResource::getUrl("view", ["record" => $contact->contactType]);
                                }
                                return null;
                            })
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('letter_sent')
                            ->label(__('contact.fields.letter_sent'))
                            ->dateTime()
                    ])->columns(3),
            ]);
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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zipcode.code')
                    ->label(__('zipcode.fields.code'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zipcode.name')
                    ->label(__('zipcode.fields.name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lang')
                    ->label(__('commune.fields.lang'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state, $record) {
                        return $record->lang ?? ($record->zipcode->commune->lang ?? 'Commune has no lang');
                    }),
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
                Tables\Columns\TextColumn::make('contactType.name')
                    ->label(__('contact.fields.contact_type'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('letter_sent')
                    ->label(__('contact.fields.letter_sent'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('letter_sent_from')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('contact.filter.letter_sent_from')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('letter_sent', '>=', $date));
                    }),

                Tables\Filters\Filter::make('letter_sent_until')
                    ->form([
                        Forms\Components\DatePicker::make('until')
                            ->label(__('contact.filter.letter_sent_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('letter_sent', '<=', $date));
                    }),
                Tables\Filters\TernaryFilter::make('letter_sent_null')
                    ->label(__('contact.filter.letter_sent_or_not'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('contact.filter.letter_sent_is_null_true'))
                    ->falseLabel(__('contact.filter.letter_sent_is_null_false'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('letter_sent'),
                        false: fn (Builder $query) => $query->whereNotNull('letter_sent'),
                    ),
                Tables\Filters\SelectFilter::make('contact_type_id')
                    ->label(__('contact.fields.contact_type'))
                    ->relationship('contactType', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ContactExporter::class),
                ImportAction::make()->importer(ContactImporter::class),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()->exporter(ContactExporter::class),
                ]),
                ExportContactsPdfBulkAction::make(),
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
