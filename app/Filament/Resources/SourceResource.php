<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Filament\Resources\SourceResource\RelationManagers;
use App\Models\Source;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SourceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-end-on-rectangle';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.systemSettings');
    }

    protected static ?int $navigationSort = 4;

    // Add model label
    public static function getModelLabel(): string
    {
        return __('source.name');
    }

    // Add plural model label
    public static function getPluralModelLabel(): string
    {
        return __('source.namePlural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make("labels")
                    ->tabs([
                        Forms\Components\Tabs\Tab::make("german")
                            ->schema([
                                Forms\Components\TextInput::make("label.de")
                                    ->label(__("input.label.source.label.de"))
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make("french")
                            ->schema([
                                Forms\Components\TextInput::make("label.fr")
                                    ->label(__("input.label.source.label.fr"))
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make("italian")
                            ->schema([
                                Forms\Components\TextInput::make("label.it")
                                    ->label(__("input.label.source.label.it"))
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(2),
                Forms\Components\TextInput::make('shortcut')
                    ->maxLength(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label.' . app()->getLocale())
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getWidgets(): array
    {
        return [
            SourceResource\Widgets\SourceStats::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
            'view' => Pages\ViewSource::route('/{record}'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'search'
        ];
    }
}
