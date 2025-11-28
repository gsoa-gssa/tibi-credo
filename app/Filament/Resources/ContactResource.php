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

    public static function getFormSchema($attachedToSheet = false): array
    {
        $schema = [
                Forms\Components\TextInput::make('firstname')
                    ->label(__('contact.fields.firstname'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('lastname')
                    ->label(__('contact.fields.lastname'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('street_no')
                    ->label(__('contact.fields.street_no'))
                    ->required()
                    ->maxLength(255),
        ];
        $schema[] = Forms\Components\Select::make('zipcode_id')
                    ->label(__('zipcode.name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} {$record->nameWithCanton()}")
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
                    });
        $schema[] = Forms\Components\DatePicker::make('birthdate')
                    ->label(__('contact.fields.birthdate'))
                    ->required();
        $schema[] = Forms\Components\ToggleButtons::make('lang')
                    ->label(__('commune.fields.lang'))
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
                    });
        if ( !$attachedToSheet ) {
            $schema[] = Forms\Components\TextInput::make('sheet_label')
                ->label(__('sheet.name'))
                ->helperText(__('pages.registerInvalid.sheet_id_helper'))
                ->live()
                ->rules([
                    function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if ($value) {
                                $sheets = \App\Models\Sheet::where('label', $value)->get();
                                if ($sheets->isEmpty()) {
                                    $fail('The sheet with this label does not exist.');
                                } elseif ($sheets->count() > 1) {
                                    $fail('Multiple sheets found with this label. Please contact support.');
                                }
                            }
                        };
                    },
                ])
                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    if ($state) {
                        $sheet = \App\Models\Sheet::where('label', $state)->first();
                        if($sheet) {
                        $set('sheet_id', $sheet->id);
                        $zipcode = $sheet->commune->zipcodes()->first();
                        if ($zipcode) {
                            $set('zipcode_id', $zipcode->id);
                            $set('lang', $zipcode->commune->lang);
                        }
                        } else {
                        $set('sheet_id', null);
                        }
                    }
                })
                ->suffixIcon(function (Forms\Get $get) {
                    if ($get('sheet_label') == null) {
                    return null;
                    }
                    $id = $get('sheet_id');
                    return $id ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle';
                })
                ->suffixIconColor(function (Forms\Get $get) {
                    if ($get('sheet_label') == null) {
                    return null;
                    }
                    $id = $get('sheet_id');
                    return $id ? 'success' : 'danger';
                });
            $schema[] = Forms\Components\Hidden::make('sheet_id');
        }
        if ( $attachedToSheet ){
            $schema[] = Forms\Components\Select::make('contact_type_id')
                    ->label(__('contact.fields.contact_type'))
                    ->relationship('contactType', 'name')
                    ->required()
                    ->default(1)
                    ->searchable()
                    ->preload();
        } else {
            $schema[] = Forms\Components\Select::make('contact_type_id')
                ->label(__('contact.fields.contact_type'))
                ->relationship('contactType', 'name')
                ->required()
                ->searchable()
                ->preload();
        }
        
        if ( !$attachedToSheet ){
            $schema[] = Forms\Components\DateTimePicker::make('letter_sent')
                    ->label(__('contact.fields.letter_sent'));
            $schema[] = Forms\Components\Checkbox::make('address_corrected')
                    ->label(__('contact.fields.address_corrected'));
            $schema[] = Forms\Components\Checkbox::make('address_uncorrectable')
                    ->label(__('contact.fields.address_uncorrectable'));
        }
        return $schema;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
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
                            ->dateTime(),
                        Infolists\Components\IconEntry::make('address_corrected')
                            ->label(__('contact.fields.address_corrected'))
                            ->boolean(),
                        Infolists\Components\IconEntry::make('address_uncorrectable')
                            ->label(__('contact.fields.address_uncorrectable'))
                            ->boolean(),
                    ])->columns(5),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zipcode.name')
                    ->label(__('zipcode.fields.name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zipcode.commune.canton.label')
                    ->label(__('canton.name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lang')
                    ->label(__('commune.fields.lang'))
                    ->searchable()
                    ->sortable()
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
            ->defaultSort('created_at', 'desc')
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
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('contact.filter.letter_sent_is_null_true'))
                    ->falseLabel(__('contact.filter.letter_sent_is_null_false'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('letter_sent'),
                        false: fn (Builder $query) => $query->whereNotNull('letter_sent'),
                    ),
                Tables\Filters\TernaryFilter::make('address_corrected')
                    ->label(__('contact.filter.address_corrected_or_not'))
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('contact.filter.address_corrected_is_true'))
                    ->falseLabel(__('contact.filter.address_corrected_is_false'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('address_corrected', true),
                        false: fn (Builder $query) => $query->where('address_corrected', false),
                    ),
                Tables\Filters\TernaryFilter::make('address_uncorrectable')
                    ->label(__('contact.filter.address_uncorrectable_or_not'))
                    ->placeholder(__('filter.all'))
                    ->trueLabel(__('contact.filter.address_uncorrectable_is_true'))
                    ->falseLabel(__('contact.filter.address_uncorrectable_is_false'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('address_uncorrectable', true),
                        false: fn (Builder $query) => $query->where('address_uncorrectable', false),
                    ),
                Tables\Filters\SelectFilter::make('contact_type_id')
                    ->label(__('contact.fields.contact_type'))
                    ->relationship('contactType', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('zipcode.commune.canton_id')
                    ->label(__('canton.name'))
                    ->relationship('zipcode.commune.canton', 'label')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('lang')
                    ->label(__('commune.fields.lang'))
                    ->options([
                        'de' => __('input.label.source.label.de'),
                        'fr' => __('input.label.source.label.fr'),
                        'it' => __('input.label.source.label.it'),
                    ])
                    ->searchable(),
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
                    Tables\Actions\BulkAction::make('assign_contact_type')
                    ->label(__('contact.bulk_action.assign_contact_type'))
                    ->form([
                        Forms\Components\Select::make('contact_type')
                            ->label(__('Contact Type'))
                            ->options(\App\Models\ContactType::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $contact) {
                            $contact->contact_type_id = $data['contact_type'];
                            $contact->save();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                ]),
                Tables\Actions\BulkActionGroup::make([
                    ExportContactsPdfBulkAction::make('letters_left')
                        ->addressPosition('left')
                        ->priorityMail(false)
                        ->label(__('contact.letter.action.label.left')),
                    ExportContactsPdfBulkAction::make('letters_left_priority')
                        ->addressPosition('left')
                        ->priorityMail(true)
                        ->label(__('contact.letter.action.label.left_priority')),
                    ExportContactsPdfBulkAction::make('letters_right')
                        ->addressPosition('right')
                        ->priorityMail(false)
                        ->label(__('contact.letter.action.label.right')),
                    ExportContactsPdfBulkAction::make('letters_right_priority')
                        ->addressPosition('right')
                        ->priorityMail(true)
                        ->label(__('contact.letter.action.label.right_priority')),
                ])->label(__('contact.letter.action.label')),
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
