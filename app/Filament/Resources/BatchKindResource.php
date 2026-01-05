<?php

namespace App\Filament\Resources;

use App\Models\BatchKind;
use App\Filament\Resources\BatchKindResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchKindResource extends Resource
{
    protected static ?string $model = BatchKind::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('batchKind.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('batchKind.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('signature_collection_id')
                    ->default(fn () => auth()->user()?->signature_collection_id)
                    ->required(),
                Forms\Components\Section::make(__('batchKind.sections.short_names'))
                    ->schema([
                        Forms\Components\TextInput::make('short_name_de')
                            ->label('Deutsch')
                            ->required(),
                        Forms\Components\TextInput::make('short_name_fr')
                            ->label('Français')
                            ->required(),
                        Forms\Components\TextInput::make('short_name_it')
                            ->label('Italiano')
                            ->required(),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.subject_fixed'))
                    ->schema([
                        Forms\Components\Placeholder::make('subject_fixed_de')
                            ->label('')
                            ->content(fn ($record) => $record?->signatureCollection?->official_name_de ?? ''),
                        Forms\Components\Placeholder::make('subject_fixed_fr')
                            ->label('')
                            ->content(fn ($record) => $record?->signatureCollection?->official_name_fr ?? ''),
                        Forms\Components\Placeholder::make('subject_fixed_it')
                            ->label('')
                            ->content(fn ($record) => $record?->signatureCollection?->official_name_it ?? ''),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.subject_variable'))
                    ->schema([
                        Forms\Components\TextInput::make('subject_de')
                            ->label('Deutsch'),
                        Forms\Components\TextInput::make('subject_fr')
                            ->label('Français'),
                        Forms\Components\TextInput::make('subject_it')
                            ->label('Italiano'),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.subject_addition'))
                    ->schema([
                        Forms\Components\Placeholder::make('reminder_subject_addition_de')
                            ->label('Deutsch')
                            ->content(fn () => __('batch.letter.reminder.subject_addition')),
                        Forms\Components\Placeholder::make('reminder_subject_addition_fr')
                            ->label('Français')
                            ->content(fn () => __('batch.letter.reminder.subject_addition')),
                        Forms\Components\Placeholder::make('reminder_subject_addition_it')
                            ->label('Italiano')
                            ->content(fn () => __('batch.letter.reminder.subject_addition')),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.body_fixed_intro'))
                    ->schema([
                        Forms\Components\Placeholder::make('body_fixed_de')
                            ->label('Deutsch')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                __('letter.greeting', [], 'de') . '<br>' . app('translator')->get('letter.intro.' . $record->signatureCollection->type->value, [
                                    'sheets_count' => 'XXX',
                                    'signature_count' => 'YYY',
                                    'name' => $record->signatureCollection->official_name_de
                                ], 'de')
                            )),
                        Forms\Components\Placeholder::make('body_fixed_fr')
                            ->label('Français')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                __('letter.greeting', [], 'fr') . '<br>' . app('translator')->get('letter.intro.' . $record->signatureCollection->type->value, [
                                    'sheets_count' => 'XXX',
                                    'signature_count' => 'YYY',
                                    'name' => $record->signatureCollection->official_name_fr
                                ], 'fr')
                            )),
                        Forms\Components\Placeholder::make('body_fixed_it')
                            ->label('Italiano')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                __('letter.greeting', [], 'it') . '<br>' . app('translator')->get('letter.intro.' . $record->signatureCollection->type->value, [
                                    'sheets_count' => 'XXX',
                                    'signature_count' => 'YYY',
                                    'name' => $record->signatureCollection->official_name_it
                                ], 'it')
                            )),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.body_variable'))
                    ->schema([
                        Forms\Components\RichEditor::make('body_de')
                            ->label('Deutsch'),
                        Forms\Components\RichEditor::make('body_fr')
                            ->label('Français'),
                        Forms\Components\RichEditor::make('body_it')
                            ->label('Italiano'),
                    ]),
                Forms\Components\Section::make(__('batchKind.sections.body_fixed_request'))
                    ->schema([
                        Forms\Components\Placeholder::make('body_fixed_de')
                            ->label('Deutsch')
                            ->content(fn ($record) => app('translator')->get('letter.request', ['deadline' => 'XXX'], 'de')),
                        Forms\Components\Placeholder::make('body_fixed_fr')
                            ->label('Français')
                            ->content(fn ($record) => app('translator')->get('letter.request', ['deadline' => 'XXX'], 'fr')),
                        Forms\Components\Placeholder::make('body_fixed_it')
                            ->label('Italiano')
                            ->content(fn ($record) => app('translator')->get('letter.request', ['deadline' => 'XXX'], 'it')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('short_name_de')
                    ->label('Deutsch')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_name_fr')
                    ->label('Français')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_name_it')
                    ->label('Italiano')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListBatchKinds::route('/'),
            'create' => Pages\CreateBatchKind::route('/create'),
            'edit' => Pages\EditBatchKind::route('/{record}/edit'),
        ];
    }
}
