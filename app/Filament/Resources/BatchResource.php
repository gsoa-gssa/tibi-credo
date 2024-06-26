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

    protected static ?string $navigationGroup = 'Sheet Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('sendDate'),
                Forms\Components\Select::make('commune_id')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Commune::whereHas('zipcodes', fn (Builder $query) => $query->where('code', 'like', "%$search%"))->limit(10)->pluck('name', 'id')->toArray())
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commune.name')
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
                    ->numeric()
                    ->getStateUsing(fn (Batch $batch) => $batch->sheets->count())
                    ->sortable(),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('commune')
                    ->relationship('commune', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'returned' => 'Returned',
                    ])
                    ->default('pending')
                    ->label('Status')
                    ->multiple(),
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
