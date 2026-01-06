<?php

namespace App\Filament\Resources;

use App\Enums\SignatureCollectionType;
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
            TextInput::make('short_name')
                ->label(__('signature_collection.fields.short_name'))
                ->required(),
            Forms\Components\Select::make('type')
                ->label(__('signature_collection.fields.type'))
                ->options(SignatureCollectionType::options())
                ->required()
                ->default('federal_initiative'),
            Forms\Components\ColorPicker::make('color')
                ->label(__('signature_collection.fields.color'))
                ->required()
                ->helperText(__('signature_collection.color_helper'))
                ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                ->live()
                ->rule('regex:/^#[A-Fa-f0-9]{6}$/'),
            TextInput::make('valid_signatures_goal')
                ->label(__('signature_collection.fields.valid_signatures_goal'))
                ->numeric()
                ->minValue(0),
            Forms\Components\Textarea::make('return_address_letters')
                ->label(__('signature_collection.fields.return_address_letters'))
                ->helperText(__('signature_collection.return_address_letters_helper'))
                ->rows(3),
            Forms\Components\Textarea::make('return_address_parcels')
                ->label(__('signature_collection.fields.return_address_parcels'))
                ->helperText(__('signature_collection.return_address_parcels_helper'))
                ->rows(3),
            Forms\Components\Tabs::make('Languages')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Deutsch')
                        ->schema([
                            TextInput::make('official_name_de')
                                ->label(__('signature_collection.fields.official_name_de'))
                                ->required(),
                            TextInput::make('responsible_person_name_de')
                                ->label(__('signature_collection.fields.responsible_person_name_de'))
                                ->required(),
                            TextInput::make('responsible_person_email_de')
                                ->label(__('signature_collection.fields.responsible_person_email_de'))
                                ->email()
                                ->required(),
                            TextInput::make('responsible_person_phone_de')
                                ->label(__('signature_collection.fields.responsible_person_phone_de'))
                                ->required(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Français')
                        ->schema([
                            TextInput::make('official_name_fr')
                                ->label(__('signature_collection.fields.official_name_fr'))
                                ->required(),
                            TextInput::make('responsible_person_name_fr')
                                ->label(__('signature_collection.fields.responsible_person_name_fr'))
                                ->required(),
                            TextInput::make('responsible_person_email_fr')
                                ->label(__('signature_collection.fields.responsible_person_email_fr'))
                                ->email()
                                ->required(),
                            TextInput::make('responsible_person_phone_fr')
                                ->label(__('signature_collection.fields.responsible_person_phone_fr'))
                                ->required(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Italiano')
                        ->schema([
                            TextInput::make('official_name_it')
                                ->label(__('signature_collection.fields.official_name_it'))
                                ->required(),
                            TextInput::make('responsible_person_name_it')
                                ->label(__('signature_collection.fields.responsible_person_name_it'))
                                ->required(),
                            TextInput::make('responsible_person_email_it')
                                ->label(__('signature_collection.fields.responsible_person_email_it'))
                                ->email()
                                ->required(),
                            TextInput::make('responsible_person_phone_it')
                                ->label(__('signature_collection.fields.responsible_person_phone_it'))
                                ->required(),
                        ]),
                ])
                ->columnSpan(2),
            DatePicker::make('publication_date')
                ->label(__('signature_collection.fields.publication_date')),
            DatePicker::make('end_date')
                ->label(__('signature_collection.fields.end_date')),
            Forms\Components\Section::make(__('signature_collection.sections.pp'))
                ->schema([
                    TextInput::make('pp_sender_zipcode')
                        ->label(__('signature_collection.fields.pp_sender_zipcode')),
                    Forms\Components\Tabs::make('PP Sender Languages')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Deutsch')
                                ->schema([
                                    TextInput::make('pp_sender_place_de')
                                        ->label(__('signature_collection.fields.pp_sender_place_de')),
                                    TextInput::make('pp_sender_name_de')
                                        ->label(__('signature_collection.fields.pp_sender_name_de')),
                                ]),
                            Forms\Components\Tabs\Tab::make('Français')
                                ->schema([
                                    TextInput::make('pp_sender_place_fr')
                                        ->label(__('signature_collection.fields.pp_sender_place_fr')),
                                    TextInput::make('pp_sender_name_fr')
                                        ->label(__('signature_collection.fields.pp_sender_name_fr')),
                                ]),
                            Forms\Components\Tabs\Tab::make('Italiano')
                                ->schema([
                                    TextInput::make('pp_sender_place_it')
                                        ->label(__('signature_collection.fields.pp_sender_place_it')),
                                    TextInput::make('pp_sender_name_it')
                                        ->label(__('signature_collection.fields.pp_sender_name_it')),
                                ]),
                        ])
                ])
                ->columnSpan(2)
                ->label(__('signature_collection.sections.pp')),
            TextInput::make('post_ch_ag_billing_number')
                ->label(__('signature_collection.fields.post_ch_ag_billing_number'))
                ->helperText(__('signature_collection.helpers.post_ch_ag_billing_number'))
                ->rules(['nullable', 'regex:/^\d{8}$/'])
                ->maxLength(8)
                ->columnSpan('full'),
            TextInput::make('return_workdays')
                ->label(__('signature_collection.fields.return_workdays'))
                ->numeric()
                ->minValue(0)
                ->helperText(__('signature_collection.return_workdays_helper')),
            Forms\Components\Select::make('default_send_kind_id')
                ->label(__('signature_collection.fields.default_send_kind'))
                ->relationship('defaultSendKind', 'short_name_de')
                ->helperText(__('signature_collection.helpers.default_send_kind'))
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_name')
                    ->label(__('signature_collection.fields.short_name')),
                TextColumn::make('type')
                    ->label(__('signature_collection.fields.type'))
                    ->formatStateUsing(fn (SignatureCollectionType $state): string => $state->label())
                    ->badge(),
                TextColumn::make('valid_signatures_goal')
                    ->label(__('signature_collection.fields.valid_signatures_goal'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('return_address_letters')
                    ->label(__('signature_collection.fields.return_address_letters'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('return_address_parcels')
                    ->label(__('signature_collection.fields.return_address_parcels'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_name_de')
                    ->label(__('signature_collection.fields.official_name_de'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_name_fr')
                    ->label(__('signature_collection.fields.official_name_fr'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_name_it')
                    ->label(__('signature_collection.fields.official_name_it'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('publication_date')
                    ->label(__('signature_collection.fields.publication_date'))
                    ->date(),
                TextColumn::make('end_date')
                    ->label(__('signature_collection.fields.end_date'))
                    ->date(),
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
