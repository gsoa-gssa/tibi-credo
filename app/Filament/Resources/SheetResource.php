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
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;


class SheetResource extends Resource
{
    protected static ?string $model = Sheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('sheet.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('sheet.namePlural');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Toggle::make('vox')
                        ->default(true),
                    Forms\Components\TextInput::make("label")
                        ->required(),
                    Forms\Components\Select::make("source_id")
                        ->required()
                        ->relationship('source', 'code')
                        ->searchable()
                        ->native(false),
                ])->columns(3),
                Forms\Components\Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(
                        function (string $search): array {
                            // if $search can be cast to int...
                            if (is_numeric($search)) {
                                $zipcodes = Zipcode::where('code', 'like', "%$search%")->limit(10)->get();
                                $results = [];
                                foreach ($zipcodes as $zipcode) {
                                    $results[] = [
                                        $zipcode->commune->id => $zipcode->commune->name . ' (' . $zipcode->name . ')',
                                    ];
                                }
                            } else {
                                $communes = Commune::where('name', 'like', "%$search%")->limit(10)->get();
                                foreach ($communes as $commune) {
                                    $results[] = [
                                        $commune->id => $commune->name,
                                    ];
                                }
                            }
                            
                            return $results;
                        })
                    ->default(
                        fn () => auth()->user()->getCommuneId(),
                    )
                    ->required(),
                Forms\Components\TextInput::make('signatureCount')
                    ->required()
                    ->numeric(),
                Forms\Components\Group::make([
                    Forms\Components\Select::make('maeppli_id')
                        ->label(__('sheet.maeppli'))
                        ->relationship('maeppli', 'label')
                        ->searchable()
                        ->preload()
                        ->placeholder(__('input.placeholder.select_maeppli')),
                    Forms\Components\Select::make('batch_id')
                        ->label(__('sheet.batch'))
                        ->relationship('batch', 'id', modifyQueryUsing: function ($query, $get) {
                            $communeId = $get('commune_id');
                            $batchId = $get('batch_id');
                            $query->where(function (Builder $query) use ($communeId, $batchId) {
                                $query->where('commune_id', $communeId);
                                $query->orWhere('id', $batchId);
                            });
                        })
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->id . ': ' . $record->commune->name . ', ' . $record->created_at->format('Y-m-d'))
                        ->searchable()
                        ->preload()
                        ->placeholder(__('input.placeholder.select_batch')),
                ])->columns(2),
                Forms\Components\Hidden::make('user_id')
                    ->required()
                    ->default(auth()->id()),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->formatStateUsing(fn (string $state): string => str_starts_with($state, 'VOX') ? $state : sprintf('%06d', $state))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signatureCount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('maeppli_id')
                    ->label(__('sheet.maeppli'))
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->url(fn ($record) => $record->maeppli ?
                        \App\Filament\Resources\MaeppliResource::getUrl('view', ['record' => $record->maeppli]) :
                        null)
                    ->extraAttributes(fn ($record) => $record->maeppli ? [
                        'title' => $record->maeppli->label,
                    ] : []),
                Tables\Columns\IconColumn::make('batch_id')
                    ->label(__('sheet.batch'))
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->url(fn ($record) => $record->batch ?
                        \App\Filament\Resources\BatchResource::getUrl('view', ['record' => $record->batch]) :
                        null)
                    ->extraAttributes(fn ($record) => $record->batch ? [
                        'title' => __('sheet.batch') . ': ' . $record->batch->id,
                    ] : []),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('commune')
                    ->label(__('sheet.commune'))
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_maeppli')
                    ->label(__('sheet.filters.has_maeppli'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('maeppli_id')),
                Tables\Filters\Filter::make('no_maeppli')
                    ->label(__('sheet.filters.no_maeppli'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('maeppli_id')),
                Tables\Filters\Filter::make('has_batch')
                    ->label(__('sheet.filters.has_batch'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('batch_id')),
                Tables\Filters\Filter::make('no_batch')
                    ->label(__('sheet.filters.no_batch'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('batch_id')),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make("demovox")
                    ->label("Demo Vox")
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('vox', true)),
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
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()->exporter(\App\Filament\Exports\SheetExporter::class),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SheetResource\RelationManagers\ContactsRelationManager::class,
            SheetResource\RelationManagers\SheetsActivitylogRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSheets::route('/'),
            'create' => Pages\CreateSheet::route('/create'),
            'edit' => Pages\EditSheet::route('/{record}/edit'),
            'activities' => Pages\ActivityLogPage::route('/{record}/activities'),
            'view' => Pages\ViewSheet::route('/{record}'),
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
