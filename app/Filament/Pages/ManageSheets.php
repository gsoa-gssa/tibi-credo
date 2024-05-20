<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use App\Settings\SheetsSettings;
use Filament\Pages\SettingsPage;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageSheets extends SettingsPage
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 3;

    protected static string $settings = SheetsSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('sources')
                ->schema([
                    TextInput::make('value')
                        ->label('Value')
                        ->required()
                        ->maxLength(3),
                    TextInput::make('label')
                        ->label('Label')
                        ->required()
                        ->maxLength(255),
                ])
                ->addActionLabel('Add Source')
            ]);
    }
}
