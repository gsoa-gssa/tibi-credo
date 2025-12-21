<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoxResource\Pages;
use App\Filament\Resources\BoxResource\RelationManagers\MaepplisRelationManager;
use App\Models\Box;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class BoxResource extends Resource
{
    protected static ?string $model = Box::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function getModelLabel(): string
    {
        return __('box.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('box.namePlural');
    }
    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.sheetManagement');
    }

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // No editable persisted fields; Box has computed accessors
            Forms\Components\Placeholder::make('label')
                ->content(fn(?Box $record) => $record?->label ?? '—')
                ->label(__('box.fields.label')),
            Forms\Components\Placeholder::make('label_final')
                ->content(fn(?Box $record) => $record?->label_final ?? '—')
                ->label(__('box.fields.label_final')),
            Forms\Components\Placeholder::make('canton')
                ->content(fn(?Box $record) => $record?->canton ?? '—')
                ->label(__('canton.name')),
            Forms\Components\Placeholder::make('signatures')
                ->content(fn(?Box $record) => $record?->signatures_count ?? 0)
                ->label(__('box.fields.signature_count')),
            Forms\Components\Placeholder::make('signatures_total')
              ->content(fn(?Box $record) => $record?->signatures_count_total ?? 0)
              ->label(__('box.fields.signature_count_total')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('box.fields.label'))
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->orderBy('id', $direction);
                    }),
                TextColumn::make('label_final')
                    ->label(__('box.fields.label_final'))
                    ->sortable(query: function (Builder $query, $direction) {
                        return $query->orderBy('id', $direction);
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('canton')
                  ->label(__('canton.name'))
                  ->toggleable()
                  ->toggledHiddenByDefault(),
                TextColumn::make('signatures_count')
                  ->label(__('box.fields.signature_count')),
                TextColumn::make('signatures_count_total')
                  ->label(__('box.fields.signature_count_total'))
                  ->toggleable()
                  ->toggledHiddenByDefault(),
                TextColumn::make('maepplis_count')
                  ->counts('maepplis')
                  ->label(__('box.fields.maeppli_count'))
                  ->toggleable()
                  ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                  ->label(__('box.fields.created_at'))
                  ->dateTime()
                  ->toggleable()->toggledHiddenByDefault(),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MaepplisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoxes::route('/'),
            'create' => Pages\CreateBox::route('/create'),
            'edit' => Pages\EditBox::route('/{record}/edit'),
        ];
    }
}
