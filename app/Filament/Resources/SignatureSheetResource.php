<?php

namespace App\Filament\Resources;

use App\Models\SignatureSheet;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SignatureSheetResource extends Resource
{
    protected static ?string $model = SignatureSheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    public static function getModelLabel(): string
    {
        return __('signatureSheet.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('signatureSheet.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('signature_collection_id')
                    ->default(fn () => auth()->user()?->signature_collection_id)
                    ->required(),
                Forms\Components\TextInput::make('short_name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('signatureSheet.fields.short_name')),
                Forms\Components\Textarea::make('description_internal')
                    ->rows(4)
                    ->label(__('signatureSheet.fields.description_internal')),
                Forms\Components\Section::make(__('signatureSheet.sections.source'))
                    ->schema([
                        Forms\Components\TextInput::make('source_x')
                            ->numeric()
                            ->label(__('signatureSheet.fields.source_x')),
                        Forms\Components\TextInput::make('source_y')
                            ->numeric()
                            ->label(__('signatureSheet.fields.source_y')),
                        Forms\Components\TextInput::make('source_font_size')
                            ->numeric()
                            ->label(__('signatureSheet.fields.source_font_size')),
                        Forms\Components\TextInput::make('source_page_number')
                            ->numeric()
                            ->label(__('signatureSheet.fields.source_page_number')),
                    ])
                    ->columns(2),
                Forms\Components\FileUpload::make('sheet_pdf')
                    ->acceptedFileTypes(['application/pdf'])
                    ->disk('public')
                    ->directory('signature-sheets')
                    ->maxSize(2048)
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->columnSpan('full')
                    ->label(__('signatureSheet.fields.sheet_pdf')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('short_name')
            ->columns([
                Tables\Columns\TextColumn::make('short_name')
                    ->label(__('signatureSheet.fields.short_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('signatureCollection.short_name')
                    ->label(__('signature_collection.name')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SignatureSheetResource\RelationManagers\SourcesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => SignatureSheetResource\Pages\ListSignatureSheets::route('/'),
            'create' => SignatureSheetResource\Pages\CreateSignatureSheet::route('/create'),
            'edit' => SignatureSheetResource\Pages\EditSignatureSheet::route('/{record}/edit'),
        ];
    }
}
