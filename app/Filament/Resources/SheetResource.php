<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SheetResource\Pages;
use App\Filament\Resources\SheetResource\RelationManagers;
use App\Models\Commune;
use App\Models\Sheet;
use App\Models\Zipcode;
use App\Settings\SheetsSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\View\LegacyComponents\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SheetResource extends Resource
{
    protected static ?string $model = Sheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sheet Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('numerator_id')
                    ->relationship('numerator', 'id')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make("source")
                    ->required()
                    ->options(
                        function() {
                            return collect(app(SheetsSettings::class)->sources)
                                ->pluck('label', 'value')
                                ->toArray();
                        }
                    )
                    ->getSearchResultsUsing(
                        function (string $search): array {
                            $sources = app(SheetsSettings::class)->sources;
                            $results = [];
                            foreach ($sources as $source) {
                                if (str_contains($source['value'], $search) || str_contains($source['label'], $search)) {
                                    $results[] = [
                                        $source['value'] => $source['label'],
                                    ];
                                }
                            }
                            return $results;
                        }
                    )
                    ->searchable()
                    ->native(false),
                Forms\Components\TextInput::make('signatureCount')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(
                        function (string $search): array {
                            $zipcodes = Zipcode::where('code', 'like', "%$search%")->limit(10)->get();
                            $results = [];
                            foreach ($zipcodes as $zipcode) {
                                $results[] = [
                                    $zipcode->commune->id => $zipcode->commune->name . ' (' . $zipcode->name . ')',
                                ];
                            }
                            return $results;
                        })
                    ->default(
                        fn () => auth()->user()->getCommuneId(),
                    )
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->required()
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numerator_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signatureCount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make("deleted_at")
                    ->icon(fn ($record) => $record->trashed() ? 'heroicon-o-trash' : null)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('commune.name')
                    ->numeric()
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('activity-log')
                        ->label('Activity Log')
                        ->icon('heroicon-o-lifebuoy')
                        ->url(fn ($record) => SheetResource::getUrl('activities', ["record"=>$record]))
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListSheets::route('/'),
            'create' => Pages\CreateSheet::route('/create'),
            'edit' => Pages\EditSheet::route('/{record}/edit'),
            'activities' => Pages\ActivityLogPage::route('/{record}/activities'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
