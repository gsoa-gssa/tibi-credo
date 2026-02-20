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
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BatchResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Filament\Actions\BulkActions\ExportBatchesBulkActionGroup;
use App\Filament\Actions\BulkActions\ShowBatchLettersBulkAction;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Illuminate\Support\Collection;
use App\Models\User;

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

    public static function infolist(InfoList $infolist): InfoList
    {
        return $infolist
            ->schema(self::getInfolistSchema());
    }

    public static function getInfolistSchema(): array
    {
        return [
            Components\TextEntry::make('commune.name_with_canton_and_zipcode')
                ->label(__('commune.name'))
                ->columnSpanFull()
                ->url(fn (Batch $record) => CommuneResource::getUrl('view', ['record' => $record->commune])),
            Components\TextEntry::make('signature_count')
                ->label(__('batch.fields.signature_count')),
            Components\TextEntry::make('sheets_count')
                ->label(__('batch.fields.sheets_count')),
            Components\TextEntry::make('weight_grams')
                ->label(__('batch.fields.weight_grams')),
            Components\IconEntry::make('open')
                ->label(__('batch.fields.open'))
                ->icon(fn ($record) => $record->open ? 'heroicon-o-clock' : 'heroicon-o-archive-box')
                ->color(fn ($record) => $record->open ? 'warning' : 'success')
                ->tooltip(fn ($record) => $record->open ? __('batch.filters.open.open') : __('batch.filters.open.closed')),
            Components\TextEntry::make('expected_delivery_date')
                ->label(__('batch.fields.expected_delivery_date')),
            Components\TextEntry::make('priority')
                ->label(__('batch.fields.priority'))
                ->columnSpan(1),
            Components\TextEntry::make('expected_return_date')
                ->label(__('batch.fields.expected_return_date')),
            Components\TextEntry::make('sendKind.short_name_de')
                ->label(__('batch.fields.send_kind')),
            Components\TextEntry::make('receiveKind.short_name_de')
                ->label(__('batch.fields.receive_kind')),
            Components\ViewEntry::make('letter_html')
                ->label(__('batch.fields.letter_preview'))
                ->view('filament.forms.components.letter-html-preview')
                ->columnSpanFull()
        ];
    }

    public static function getFormSchema(): array
    {
        return [
                Forms\Components\Hidden::make('signature_collection_id')
                    ->default(fn () => auth()->user()?->signature_collection_id)
                    ->required(),
                Forms\Components\Select::make('commune_id')
                    ->label(__('commune.name'))
                    ->columnSpan(2)
                    ->searchable()
                    ->getOptionLabelUsing(fn ($value): ?string => Commune::find($value)?->name_with_canton_and_zipcode)
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
                    ->hidden(function (Get $get, $record) {
                        if ($record !== null) {
                            return false;
                        }
                        $sheets = $get('sheets_count');
                        if (!is_numeric($sheets)) {
                            return true;
                        }
                        return ((int) $sheets) < 100;
                    })
                    ->required(fn ($record) => $record === null),
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
                        $weight = $get('weight_grams');
                        $weightSuspect = false;
                        if (is_numeric($weight)){
                            $weight = (int) $weight;
                            $expectedWeight = $sheets * 5;
                            $weightSuspect = $weight < $expectedWeight * 0.8 || $weight > $expectedWeight * 1.2;
                        }
                        $sig = (int) $sig;
                        $sheets = (int) $sheets;
                        $manySigs = $sig > 500;
                        $sigSheetRelationBig = ($sig > 15 || $sheets > 5) && $sheets > 0 && ($sig / $sheets) > 3;
                        $sigSheetRelationSmall = ($sig > 15 || $sheets > 5) && $sheets > 0 && ($sig / $sheets) < 1.5;
                        return !($manySigs || $sigSheetRelationBig || $sigSheetRelationSmall || $weightSuspect);
                    }),
                Forms\Components\ToggleButtons::make('open')
                    ->label(__('batch.fields.open'))
                    ->options([
                       false => __('batch.filters.open.closed'),
                       true => __('batch.filters.open.open'),
                    ])
                    ->inline()
                    ->columnSpan(2)
                    ->hidden(fn ($record) => $record === null),
                Forms\Components\DatePicker::make('expected_delivery_date')
                    ->label(__('batch.fields.expected_delivery_date'))
                    ->hidden(fn ($record) => $record === null),
                Forms\Components\DatePicker::make('expected_return_date')
                    ->label(__('batch.fields.expected_return_date'))
                    ->hidden(fn ($record) => $record === null),
                Forms\Components\Checkbox::make('is_problem_letter')
                    ->label(__('batch.fields.is_problem_letter'))
                    ->columnSpan(2)
                    ->default(false)
                    ->dehydrated(false)
                    ->live()
                    ->hidden(fn ($record) => $record !== null),
                Forms\Components\Select::make('send_kind')
                    ->label(__('batch.fields.send_kind'))
                    ->relationship('sendKind', 'short_name_de')
                    ->required()
                    ->default(fn () => auth()->user()->signatureCollection->default_send_kind_id)
                    ->columnSpan(fn (Get $get) => $get('is_problem_letter') ? 2 : 1)
                    ->hidden(fn (Get $get, $record) => $record === null && !$get('is_problem_letter')),
                Forms\Components\Select::make('receive_kind')
                    ->label(__('batch.fields.receive_kind'))
                    ->relationship('receiveKind', 'short_name_de')
                    ->nullable()
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
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search) => $query->orWhereHas('commune', fn (Builder $q) => $q->where('name_with_canton_and_zipcode', 'like', "%{$search}%"))),
                Tables\Columns\IconColumn::make('open')
                    ->label(__('batch.fields.open'))
                    ->icon(fn (Batch $batch) => $batch->open ? 'heroicon-o-clock' : 'heroicon-o-archive-box')
                    ->tooltip(fn (Batch $batch) => $batch->open ? __('batch.filters.open.open') : __('batch.filters.open.closed'))
                    ->color(fn (Batch $batch) => $batch->open ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('batch.fields.sheets_count'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label(__('batch.fields.priority'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('signature_count')
                    ->label(__('batch.fields.signature_count'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make("deleted_at")
                    ->icon(fn ($record) => $record->trashed() ? 'heroicon-o-trash' : null)
                    ->tooltip(fn ($record) => $record->trashed() ? 'Deleted' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label(__('batch.fields.expected_delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expected_return_date')
                    ->label(__('batch.fields.expected_return_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sendKind.short_name_de')
                    ->label(__('batch.fields.send_kind'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('receiveKind.short_name_de')
                    ->label(__('batch.fields.receive_kind'))
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
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filters([
                SelectFilter::make('commune')
                    ->relationship('commune', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nameWithCanton())
                    ->searchable()
                    ->multiple()
                    ->label(__('batch.filters.commune')),
                SelectFilter::make('created_by')
                    ->label(__('batch.filters.created_by'))
                    ->options(fn () => User::where('signature_collection_id', auth()->user()->signature_collection_id)->orderBy('name')->pluck('name', 'id')->toArray())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }
                        return $query->whereHas('activities', function (Builder $q) use ($data) {
                            $q->whereIn('causer_id', $data['values'])
                              ->where('event', 'created');
                        });
                    }),
                SelectFilter::make('open')
                    ->options([
                        true => __('batch.filters.open.open'),
                        false => __('batch.filters.open.closed'),
                    ])
                    ->label(__('batch.filters.open'))
                    ->multiple(),
                SelectFilter::make('priority')
                    ->options([
                        'A' => 'A',
                        'B1' => 'B1',
                        'B2' => 'B2',
                    ])
                    ->label(__('batch.fields.priority'))
                    ->multiple(),
                SelectFilter::make('send_kind')
                    ->relationship('sendKind', 'short_name_de')
                    ->label(__('batch.fields.send_kind'))
                    ->multiple(),
                SelectFilter::make('receive_kind')
                    ->relationship('receiveKind', 'short_name_de')
                    ->label(__('batch.fields.receive_kind'))
                    ->multiple(),
                Filter::make('expected_return_date_from')
                    ->form([
                        Forms\Components\DatePicker::make('expected_return_date_from')
                            ->label(__('batch.filters.expected_return_date_from')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['expected_return_date_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('expected_return_date', '>=', $date));
                    })
                    ->label(__('batch.filters.expected_return_date_from')),

                Filter::make('expected_return_date_to')
                    ->form([
                        Forms\Components\DatePicker::make('expected_return_date_to')
                            ->label(__('batch.filters.expected_return_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['expected_return_date_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('expected_return_date', '<=', $date));
                    })
                    ->label(__('batch.filters.expected_return_date_to')),
                Filter::make('age')
                    ->form([
                        Forms\Components\DatePicker::make('age_before')
                            ->label(__('batch.filters.age')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['age_before'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<', $date));
                    })
                    ->label(__('batch.filters.age')),
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
                ]),
                ShowBatchLettersBulkAction::make(),
                Tables\Actions\BulkAction::make('setLetterHtmlNull')
                    ->label(__('batch.action.setLettersHtmlNull'))
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->all();
                        if (!empty($ids)) {
                            Batch::whereIn('id', $ids)->update(['letter_html' => null]);
                        }
                    }),
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
