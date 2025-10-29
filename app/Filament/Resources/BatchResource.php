<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Batch;
use App\Models\Sheet;
use App\Models\Commune;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BatchResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Filament\Resources\BatchResource\RelationManagers\SheetsRelationManager;
use Filament\Tables\Filters\Filter;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('batch.name');
    }
    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('batch.namePlural');
    }

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('sendDate'),
                Forms\Components\Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Commune::where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%$search%")
                              ->orWhereHas('zipcodes', fn (Builder $q) => $q->where('code', 'like', "%$search%"));
                    })->limit(10)->get()->mapWithKeys(function ($commune) {
                        return [$commune->id => $commune->nameWithCanton()];
                    })->toArray())
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commune.name')
                    ->formatStateUsing(fn ($record) => $record->commune->nameWithCanton())
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (Batch $batch) => match ($batch->status) {
                        'pending' => 'heroicon-o-inbox',
                        'sent' => 'heroicon-o-building-library',
                        'returned' => 'heroicon-o-check-circle',
                    })
                    ->tooltip(fn (Batch $batch) => match ($batch->status) {
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'returned' => 'Returned',
                    })
                    ->default('pending')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_sheets')
                    ->label(__('batch.sheets_count'))
                    ->numeric()
                    ->getStateUsing(fn (Batch $batch) => $batch->sheets->count())
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->withCount('sheets')->orderBy('sheets_count', $direction);
                    }),
                Tables\Columns\TextColumn::make('returned_sheets_count')
                    ->label(__('batch.sheets_returned_count'))
                    ->numeric()
                    ->getStateUsing(fn (Batch $batch) => $batch->sheets->whereNotNull('maeppli_id')->count()),
                Tables\Columns\IconColumn::make("deleted_at")
                    ->icon(fn ($record) => $record->trashed() ? 'heroicon-o-trash' : null)
                    ->tooltip(fn ($record) => $record->trashed() ? 'Deleted' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sendDate')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('commune')
                    ->relationship('commune', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nameWithCanton())
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->label(__('batch.filters.commune')),
                SelectFilter::make('status')
                    ->options([
                        'pending' => __('batch.filters.status.pending'),
                        'sent' => __('batch.filters.status.sent'),
                        'returned' => __('batch.filters.status.returned'),
                    ])
                    ->default('pending')
                    ->label(__('batch.filters.status'))
                    ->multiple(),
                Filter::make('partially_returned')
                    ->label(__('batch.filters.partially_returned'))
                    ->toggle()
                    ->query(function (Builder $query) {
                        $query 
                            ->whereHas('sheets', function (Builder $query) {
                                $query->whereNotNull('maeppli_id');
                            })
                            ->whereHas('sheets', function (Builder $query) {
                                $query->whereNull('maeppli_id');
                            });
                    }),
                SelectFilter::make('age')
                    ->label(__('batch.filters.age'))
                    ->options([
                        '2_weeks' => __('batch.filters.age.2_weeks'),
                        '4_weeks' => __('batch.filters.age.4_weeks'),
                        '6_weeks' => __('batch.filters.age.6_weeks'),
                        '8_weeks' => __('batch.filters.age.8_weeks'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $value): Builder {
                                $weeks = match($value) {
                                    '2_weeks' => 2,
                                    '4_weeks' => 4,
                                    '6_weeks' => 6,
                                    '8_weeks' => 8,
                                    default => 0,
                                };
                                
                                if ($weeks > 0) {
                                    return $query->where('created_at', '<', now()->subWeeks($weeks));
                                }
                                
                                return $query;
                            }
                        );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make("activity-log")
                    ->label("Activity Log")
                    ->url(fn (Batch $batch) => BatchResource::getUrl('activities', ['record' => $batch])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsSent')
                        ->label('Mark as Sent')
                        ->action(fn (\Illuminate\Support\Collection $batches) => $batches->each(fn (Batch $batch) => $batch->update([
                            'status' => 'sent',
                            'sendDate' => now(),
                        ])))
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-circle'),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->action(fn (\Illuminate\Support\Collection $batches) => $batches->each(fn (Batch $batch) => $batch->updateStatus()))
                        ->icon('heroicon-o-arrow-path'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SheetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
            'view' => Pages\ViewBatch::route('/{record}'),
            'activities' => Pages\ActivityLogPage::route('/{record}/activities'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
}
