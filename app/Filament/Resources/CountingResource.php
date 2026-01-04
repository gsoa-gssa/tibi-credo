<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Counting;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CountingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CountingResource\RelationManagers;
use App\Models\Source;

class CountingResource extends Resource
{
    protected static ?string $model = Counting::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.projectDataManagement');
    }

    // Add model label
    public static function getModelLabel(): string
    {
        return __('counting.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('counting.namePlural');
    }
    
    protected static ?int $navigationSort = 0;

    public static function getFormSchema(): array
    {
        $sources = Source::all()->pluck('code', 'id')->toArray();
        return [
            Forms\Components\TextInput::make('srcAndCount')
                ->label(__('counting.fields.countAndSourceShortcut'))
                ->helperText(__('counting.helper.countAndSourceShortcut'))
                ->columnSpan('full')
                ->maxLength(5)
                ->dehydrated(false)
                ->extraAttributes([
                    'wire:model' => 'srcAndCount',
                    'x-data' => '{
                        sources: ' . \Illuminate\Support\Js::from($sources) . ',
                        errorMsg: ""
                    }',
                    'x-on:input' => '
                        console.log("srcAndCount input triggered", $event.target.value);
                        console.log("Current source:", $wire.source_id);
                        console.log("Current count:", $wire.count);
                        $wire.call("get", "source_id").then(value => {
                            console.log("Actual source_id value:", value);
                        });
                        $wire.call("get", "count").then(value => {
                            console.log("Actual count value:", value);
                        });
                        let text = $event.target.value;
                        text = text.replace(/\s+/g, "");
                        text = text.toUpperCase();
                        errorMsg = "";

                        if (text) {
                            let match = text.match(/^(\d+)([a-zA-Z]*.*)$/);
                            console.log("match result", match);
                            if (match) {
                                let count = parseInt(match[1]);
                                console.log("parsed count", count);
                                if (count >= 1 && count <= 12) {
                                    $wire.set("count", count);
                                    console.log("Set count", count);

                                    source_part = match[2];
                                    // if source_part not empty
                                    if (source_part) {
                                        sourcesCandidates = Object.entries(sources).filter(([id, code]) => code.startsWith(source_part));
                                        console.log("source_part", source_part, "candidates", sourcesCandidates);

                                        if (sourcesCandidates.length === 1) {
                                            $wire.set("source_id", sourcesCandidates[0][0]);
                                            console.log("Set source_id", sourcesCandidates[0][0]);
                                        } else if (sourcesCandidates.length > 1) {
                                            let codes = sourcesCandidates.map(([id, code]) => code).join(", ");
                                            $wire.set("source_id", sourcesCandidates[0][0]);
                                            errorMsg = "Multiple source matches: " + codes;
                                            console.log("Multiple source matches", codes);
                                        } else {
                                            errorMsg = "No source match found for: " + source_part;
                                            console.log("No source match found", source_part);
                                        }
                                    } else {
                                        // if there is already a source_id set, keep it and notify user about that through errorMsg
                                        // if there is no source_id set, tell user in error message to add one
                                        if ($wire.source_id) {
                                            errorMsg = "Using existing source from last time (see below)";
                                            console.log("Using existing source_id", $wire.source_id);
                                        } else {
                                            errorMsg = "Please add a source";
                                            console.log("No source_id set");
                                        }
                                    }
                                } else {
                                    errorMsg = "Count must be between 1 and 12";
                                    console.log("Count out of range", count);
                                }
                            } else {
                                errorMsg = "Format: [count][source] (e.g. 5AB)";
                                console.log("Format error", text);
                            }
                        }

                        // Show/hide error
                        let errorEl = $el.parentElement.parentElement.querySelector(".srcAndCount-error");
                        if (!errorEl) {
                            errorEl = document.createElement("p");
                            errorEl.className = "srcAndCount-error text-sm text-danger-600 dark:text-danger-400 mt-1";
                            $el.parentElement.parentElement.appendChild(errorEl);
                        }
                        errorEl.textContent = errorMsg;
                        errorEl.style.display = errorMsg ? "block" : "none";

                        $event.target.value = text;
                        $wire.set("srcAndCount", text);
                        console.log("Final srcAndCount value", text);
                    '
            ]),
            Forms\Components\Select::make('source_id')
                ->label(__('source.name'))
                ->options(Source::all()->pluck('code', 'id'))
                ->required()
                ->searchable()
                ->extraAttributes([
                    'wire:model' => 'source_id',
                ]),
            Forms\Components\TextInput::make('count')
                ->label(__('counting.fields.count'))
                ->required()
                ->live(onBlur: true)
                ->numeric()
                ->extraAttributes([
                    'wire:model' => 'count',
                ]),
            Forms\Components\Checkbox::make('confirm_large_count')
                ->label(__('counting.fields.confirmLargeCount'))
                ->default(false)
                ->required(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) > 100)
                ->hidden(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) <= 100)
                ->dehydrated(false)
                ->columnSpan(2),
            Forms\Components\DatePicker::make('date')
                ->label(__('counting.fields.date'))
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label(__('counting.fields.name'))
                ->required(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) > 10)
                ->maxLength(255),

        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('counting.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('counting.fields.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('count')
                    ->numeric(),
                Tables\Columns\TextColumn::make('source.code')
                    ->label(__('source.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('counting.fields.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('counting.fields.description'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('source_id')
                    ->label(__('source.name'))
                    ->options(Source::all()->pluck('code', 'id'))
                    ->searchable(),
                Tables\Filters\Filter::make('description_not_null')
                    ->label(__('counting.filters.descriptionNotNull'))
                    ->query(fn (Builder $query) => $query->whereNotNull('description')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn (Counting $counting) => $counting->trashed())
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\CountingExporter::class),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountings::route('/'),
            'create' => Pages\CreateCounting::route('/create'),
            'view' => Pages\ViewCounting::route('/{record}'),
            'edit' => Pages\EditCounting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
