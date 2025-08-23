<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactTypeResource\Pages;
use App\Filament\Resources\ContactTypeResource\RelationManagers;
use App\Models\ContactType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactTypeResource extends Resource
{
    protected static ?string $model = ContactType::class;
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static int $max_subject_length = 80;
    protected static int $max_body_length = 2000;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Section::make('Subject')
                    ->schema([
                        Forms\Components\RichEditor::make('subject_de')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_subject_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_subject_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\RichEditor::make('subject_fr')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_subject_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_subject_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\RichEditor::make('subject_it')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_subject_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_subject_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                    ]),
                Forms\Components\Section::make('Body')
                    ->schema([
                        Forms\Components\RichEditor::make('body_de')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_body_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_body_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\RichEditor::make('body_fr')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_body_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_body_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\RichEditor::make('body_it')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $plainText = strip_tags($value ?? '');
                                        if (strlen($plainText) > self::$max_body_length) {
                                            $fail(__('contact_type.fields.validation.too_long', [
                                                'by' => strlen($plainText) - self::$max_body_length,
                                            ]));
                                        }
                                    };
                                },
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('description')->toggleable(),
                Tables\Columns\TextColumn::make('subject_de')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_fr')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_it')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_de')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_fr')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_it')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(50)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListContactTypes::route('/'),
            'create' => Pages\CreateContactType::route('/create'),
            'edit' => Pages\EditContactType::route('/{record}/edit'),
            'view' => Pages\ViewContactType::route('/{record}'),
        ];
    }
}
