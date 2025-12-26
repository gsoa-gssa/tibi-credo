<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Batch;
use App\Models\Commune;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BatchResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Filament\Actions\BulkActions\ExportBatchesBulkActionGroup;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.projectDataManagement');
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

    public static function getFormSchema(): array
    {
        return [
                Forms\Components\Select::make('commune_id')
                    ->label(__('commune.name'))
                    ->columnSpan(2)
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Commune::searchByNameOrZip($search)->mapWithKeys(function ($commune) {
                        return [$commune->id => $commune->name_with_canton_and_zipcode];
                    })->toArray())
                    ->required(),
                Forms\Components\TextInput::make('signature_count')
                    ->label(__('batch.fields.signature_count'))
                    ->numeric()
                    ->minValue(1)
                    ->live(onBlur: true)
                    ->required(),
                Forms\Components\TextInput::make('sheets_count')
                    ->label(__('batch.fields.sheets_count'))
                    ->numeric()
                    ->minValue(1)
                    ->live(onBlur: true)
                    ->required(),
                Forms\Components\TextInput::make('weight_grams')
                    ->label(__('batch.fields.weight_grams'))
                    ->columnSpan(2)
                    ->numeric()
                    ->minValue(1)
                    ->live(onBlur: true)
                    ->hidden(function (Get $get) {
                        $sheets = $get('sheets_count');
                        if (!is_numeric($sheets)) {
                            return true;
                        }
                        return ((int) $sheets) < 100;
                    })
                    ->required(),
                Forms\Components\Checkbox::make('confirm')
                    ->label(__('batch.fields.confirm_creation'))
                    ->columnSpan(2)
                    ->required()
                    ->dehydrated(false)
                    ->hidden(function (Get $get) {
                        $sig = $get('signature_count');
                        $sheets = $get('sheets_count');
                        if (!is_numeric($sig) || !is_numeric($sheets)) {
                            return true;
                        }
                        $sig = (int) $sig;
                        $sheets = (int) $sheets;
                        $manySigs = $sig > 500;
                        $sigSheetRelationBig = $sig > 5 && $sheets > 0 && ($sig / $sheets) > 3;
                        $sigSheetRelationSmall = $sig > 5 && $sheets > 0 && ($sig / $sheets) < 1.5;
                        return !($manySigs || $sigSheetRelationBig || $sigSheetRelationSmall);
                    }),
                Forms\Components\DatePicker::make('expectedDeliveryDate')
                    ->label(__('batch.fields.expected_delivery_date'))
                    ->hidden(fn ($record) => $record === null),
            ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function getTableSchema(): array
    {
        return [
                Tables\Columns\TextColumn::make('commune.name_with_canton_and_zipcode')
                    ->label(__('commune.name'))
                    ->url(fn (Batch $record) => static::getUrl('view', ['record' => $record]))
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
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('batch.fields.sheets_count'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signature_count')
                    ->label(__('batch.fields.signature_count'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make("deleted_at")
                    ->icon(fn ($record) => $record->trashed() ? 'heroicon-o-trash' : null)
                    ->tooltip(fn ($record) => $record->trashed() ? 'Deleted' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expectedDeliveryDate')
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
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableSchema())
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('commune')
                    ->relationship('commune', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nameWithCanton())
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->label(__('batch.filters.commune')),
                Filter::make('created_by_me')
                    ->label(__('batch.filters.created_by_me'))
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('activities', function (Builder $q) {
                            $q->where('causer_id', auth()->id())
                              ->where('event', 'created');
                        });
                    }),
                SelectFilter::make('status')
                    ->options([
                        'pending' => __('batch.filters.status.pending'),
                        'sent' => __('batch.filters.status.sent'),
                        'returned' => __('batch.filters.status.returned'),
                    ])
                    ->default('pending')
                    ->label(__('batch.filters.status'))
                    ->multiple(),
                SelectFilter::make('age')
                    ->label(__('batch.filters.age'))
                    ->options([
                        'today' => __('batch.filters.age.today'),
                        '2_weeks' => __('batch.filters.age.2_weeks'),
                        '4_weeks' => __('batch.filters.age.4_weeks'),
                        '6_weeks' => __('batch.filters.age.6_weeks'),
                        '8_weeks' => __('batch.filters.age.8_weeks'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $value): Builder {
                                if ($value == 'today') {
                                    return $query->whereDate('created_at', today());
                                } else {
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
            ->headerActions([
                Tables\Actions\Action::make('filter_my_pending_today')
                    ->label(__('batch.filters.my_pending_today'))
                    ->icon('heroicon-o-funnel')
                    ->action(function ($livewire) {
                            $filters = [
                                'age' => ['value' => 'today'],
                                'created_by_me' => ['isActive' => true],
                                'status' => ['values' => ['pending']],
                            ];

                            // Redirect to the index with filters in the query string so they persist after reload.
                            return redirect()->to(
                                \App\Filament\Resources\BatchResource::getUrl('index') .
                                '?' . http_build_query(['tableFilters' => $filters])
                            );
                    })
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBatchesBulkActionGroup::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BatchActivitylogRelationManager::class,
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
