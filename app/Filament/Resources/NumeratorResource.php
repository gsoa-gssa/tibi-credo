<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Numerator;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\NumeratorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\NumeratorResource\RelationManagers;

class NumeratorResource extends Resource
{
    protected static ?string $model = Numerator::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';
    protected static ?string $navigationGroup = 'Sheet Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->disabled(),
                Forms\Components\Select::make('type')
                    ->options([
                        'street' => 'Street',
                        'A5' => 'A5',
                        'demovox' => 'Demovox',
                    ])
                    ->default('street')
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(function () {
                        return auth()->id();
                    })
                    ->required(),
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
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make("createstreet")
                        ->label("Create Street Numerators")
                        ->action(function() {
                            $numerators = [];
                            for ($i = 0; $i < 100; $i++) {
                                $numerator = Numerator::create([
                                    'type' => 'street',
                                    'user_id' => auth()->id(),
                                ]);
                                $numerators[] = $numerator;
                            }
                            $pdf = Pdf::loadView('numerator.street', ['numerators' => $numerators]);
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, date('Y-m-d g:i:s') . '-street-numerators.pdf');
                        }),
                    Tables\Actions\Action::make("createdemovox")
                        ->label("Create Demovox Numerators")
                        ->action(function() {
                            $numerators = [];
                            for ($i = 0; $i < 100; $i++) {
                                $numerator = Numerator::create([
                                    'type' => 'demovox',
                                    'user_id' => auth()->id(),
                                ]);
                                $numerators[] = $numerator;
                            }
                            $pdf = Pdf::loadView('numerator.demovox', ['numerators' => $numerators]);
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, date('Y-m-d g:i:s') . '-demovox-numerators.pdf');
                        }),
                    Tables\Actions\Action::make("createA5")
                        ->label("Create A5 Numerators")
                        ->action(function() {
                            $numerators = [];
                            for ($i = 0; $i < 100; $i++) {
                                $numerator = Numerator::create([
                                    'type' => 'A5',
                                    'user_id' => auth()->id(),
                                ]);
                                $numerators[] = $numerator;
                            }
                            $dompdf = Pdf::setPaper('a5', 'landscape');
                            $pdf = $dompdf->loadView('numerator.A5', ['numerators' => $numerators]);
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, date('Y-m-d g:i:s') . '-A5-numerators.pdf');
                        }),
                ]),
                Tables\Actions\Action::make("uploadsheetscans")
                    ->label("Upload Sheetscans")
                    ->url(NumeratorResource::getUrl('uploadsheetscans')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make("activity-log")
                    ->label("Activity Log")
                    ->url(fn (Numerator $numerator) => NumeratorResource::getUrl('activities', ['record' => $numerator])),
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
            'index' => Pages\ListNumerators::route('/'),
            'create' => Pages\CreateNumerator::route('/create'),
            'edit' => Pages\EditNumerator::route('/{record}/edit'),
            'activities' => Pages\ActivityLogPage::route('/{record}/activities'),
            'uploadsheetscans' => Pages\UploadSheetScans::route('/uploadscans'),
        ];
    }
}
