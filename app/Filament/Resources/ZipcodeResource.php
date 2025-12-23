<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Zipcode;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Exports\ZipcodeExporter;
use App\Filament\Imports\ZipcodeImporter;
use App\Filament\Actions\UpdateZipcodesAction;
use App\Filament\Actions\ImportAddressesAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ZipcodeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ZipcodeResource\RelationManagers;

class ZipcodeResource extends Resource
{
    protected static ?string $model = Zipcode::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('zipcode.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('zipcode.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nameWithCanton())
                    ->searchable()
                    ->native(false)
                    ->required(),
                Forms\Components\TextInput::make("number_of_dwellings")
                    ->required()
                    ->numeric()
                    ->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('zipcode.fields.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('zipcode.fields.code'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('commune.name_with_canton')
                    ->label(__('commune.name'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    UpdateZipcodesAction::make(),
                    ImportAddressesAction::make(),
                    ImportAction::make()->importer(ZipcodeImporter::class),
                    ExportAction::make()->exporter(ZipcodeExporter::class),
                ])
                    ->label(__('zipcode.headerActionGroup.management'))
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('fix_canton_suffix')
                        ->label('Fix Canton Suffix')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->fixCantonSuffix();
                            }
                            Notification::make()
                                ->title('Canton suffix fixed for selected communes.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->color('primary'),
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
            'index' => Pages\ListZipcodes::route('/'),
            'create' => Pages\CreateZipcode::route('/create'),
            'edit' => Pages\EditZipcode::route('/{record}/edit'),
        ];
    }
}
