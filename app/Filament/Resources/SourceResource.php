<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Filament\Resources\SourceResource\RelationManagers;
use App\Models\Source;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SourceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-end-on-rectangle';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    protected static ?int $navigationSort = 4;

    // Add model label
    public static function getModelLabel(): string
    {
        return __('source.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('source.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('signature_collection_id')
                    ->default(fn () => auth()->user()?->signature_collection_id)
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label(__('source.fields.code'))
                    ->required()
                    ->columnSpan(3)
                    ->maxLength(2)
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        $set('code', strtoupper($state));
                    })
                    ->extraAttributes([
                        'x-on:input' => '
                            console.log($event.target.value);
                            $event.target.value = $event.target.value.toUpperCase();',
                    ]),
                Forms\Components\TextInput::make('short_description_de')
                    ->label(__('source.fields.short_description_de'))
                    ->rule(function (Forms\Get $get) {
                        $fr = $get('short_description_fr');
                        $it = $get('short_description_it');
                        $de = $get('short_description_de');
                        if (empty($de) && empty($fr) && empty($it)) {
                            return 'required';
                        }
                        return 'nullable';
                    })
                    ->validationMessages([
                        'required' => __('source.validations.at_least_one_language'),
                    ])
                    ->nullable(),
                Forms\Components\TextInput::make('short_description_fr')
                    ->label(__('source.fields.short_description_fr'))
                    ->rule(function (Forms\Get $get) {
                        $fr = $get('short_description_fr');
                        $it = $get('short_description_it');
                        $de = $get('short_description_de');
                        if (empty($de) && empty($fr) && empty($it)) {
                            return 'required';
                        }
                        return 'nullable';
                    })
                    ->validationMessages([
                        'required' => __('source.validations.at_least_one_language'),
                    ])
                    ->nullable(),
                Forms\Components\TextInput::make('short_description_it')
                    ->label(__('source.fields.short_description_it'))
                    ->rule(function (Forms\Get $get) {
                        $fr = $get('short_description_fr');
                        $it = $get('short_description_it');
                        $de = $get('short_description_de');
                        if (empty($de) && empty($fr) && empty($it)) {
                            return 'required';
                        }
                        return 'nullable';
                    })
                    ->validationMessages([
                        'required' => __('source.validations.at_least_one_language'),
                    ])
                    ->nullable(),
                Forms\Components\TextInput::make('sheets_printed')
                    ->label(__('source.fields.sheets_printed'))
                    ->numeric(),
                Forms\Components\TextInput::make('addition_cost')
                    ->label(__('source.fields.addition_cost'))
                    ->numeric(),
                Forms\Components\Textarea::make('comments')
                    ->label(__('source.fields.comments'))
                    ->rows(3)
                    ->columnSpan('full')
                    ->nullable(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('source.fields.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_description')
                    ->label(__('source.fields.short_description'))
                    ->getStateUsing(fn (Source $record) => $record->getLocalized('short_description'))
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('short_description_de', 'like', "%{$search}%")
                              ->orWhere('short_description_fr', 'like', "%{$search}%")
                              ->orWhere('short_description_it', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('sheets_printed')
                    ->label(__('source.fields_short.sheets_printed'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('addition_cost')
                    ->label(__('source.fields_short.addition_cost'))
                    ->money('CHF')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SourceResource\Widgets\SourceStats::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
            'view' => Pages\ViewSource::route('/{record}'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'search'
        ];
    }
}
