<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Commune;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use App\Filament\Exports\CommuneExporter;
use App\Filament\Imports\CommuneImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CommuneResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CommuneResource\RelationManagers;

class CommuneResource extends Resource
{
    protected static ?string $model = Commune::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('officialId')
                    ->required()
                    ->numeric(),
                Forms\Components\RichEditor::make('address')
                    ->nullable(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->nullable(),
                Forms\Components\TextInput::make('website')
                    ->url()
                    ->nullable(),
                Forms\Components\TextInput::make('phone')
                    ->nullable(),
                Forms\Components\Select::make('addressgroup')
                    ->options([
                        'none' => 'None',
                        'svegeneve' => 'Service des votations et éléctions de Genève',
                    ])
                    ->default('none')
                    ->label('Address Group')
                    ->required(),
                Forms\Components\Select::make('canton_id')
                    ->relationship('canton', 'label')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Canton'),
                Forms\Components\ToggleButtons::make('lang')
                    ->options([
                        'de' => 'German',
                        'fr' => 'French',
                        'it' => 'Italian',
                    ])
                    ->required()
                    ->inline()
                    ->default('de'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('officialId')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->sortable(query: function (Builder $query, $direction) {
                        $query->withCount(['sheets as sheets_count' => function($query) {
                            $query->where("status", "recorded");
                        }])->orderBy("sheets_count", $direction);
                    })
                    ->label("Sheets without Batch")
                    ->getStateUsing(function (Model $record) {
                        return $record->sheets()->where("status", "recorded")->count();
                    }),
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
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                ImportAction::make()->importer(CommuneImporter::class),
                ExportAction::make()->exporter(CommuneExporter::class),
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

    public static function getWidgets(): array
    {
        return [
            CommuneResource\Widgets\CommuneStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunes::route('/'),
            'create' => Pages\CreateCommune::route('/create'),
            'edit' => Pages\EditCommune::route('/{record}/edit'),
            'view' => Pages\ViewCommune::route('/{record}'),
        ];
    }
}
