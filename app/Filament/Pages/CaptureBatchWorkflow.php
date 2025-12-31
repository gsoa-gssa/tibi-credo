<?php

namespace App\Filament\Pages;

use App\Models\Maeppli;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CaptureBatchWorkflow extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.capture-batch-workflow';

    public ?array $data = [];

    public function getTitle(): string
    {
        return __('pages.captureBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.captureBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.captureBatchWorkflow.navigationGroup');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('commune_id')
                    ->label(__('commune.name'))
                    ->relationship('commune', 'name_with_canton_and_zipcode')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('signatures_valid_count')
                    ->label(__('maeppli.fields.signatures_valid_count'))
                    ->numeric()
                    ->live()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
                Forms\Components\TextInput::make('signatures_invalid_count')
                    ->label(__('maeppli.fields.signatures_invalid_count'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
                Forms\Components\TextInput::make('sheets_count')
                    ->label(__('maeppli.fields.sheets_count'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->helperText(__('maeppli.sheets_count_helper'))
                    ->columnSpan(2),
                Forms\Components\TextInput::make('weight_grams')
                    ->label(__('batch.fields.weight_grams'))
                    ->numeric()
                    ->columnSpan(2)
                    ->minValue(0)
                    ->maxValue(5000)
                    ->helperText(__('batch.weight_grams_helper'))
                    ->hidden(function (Get $get, $record) {
                        if ($record !== null) {
                            return false;
                        }
                        $sheets = $get('signatures_valid_count');
                        if (!is_numeric($sheets)) {
                            return true;
                        }
                        return ((int) $sheets) < 100;
                    })
                    ->required(fn ($record) => $record === null),
                Forms\Components\Checkbox::make('suspect_values')
                    ->label(__('maeppli.fields.suspect_values'))
                    ->dehydrated(false)
                    ->required()
                    ->columnSpan(2)
                    ->helperText(__('maeppli.suspect_values_helper')),
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Maeppli::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Maeppli::query()
                    ->with('commune.canton')
                    ->whereDate('created_at', today())
                    ->whereHas('activities', function ($q) {
                        $q->where('causer_id', auth()->id())
                          ->where('event', 'created');
                    })
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('display_label_html')
                    ->label(__('maeppli.fields.label'))
                    ->html()
                    ->getStateUsing(fn ($record) => $record->display_label_html),
                Tables\Columns\TextColumn::make('commune.name')
                    ->label(__('commune.name')),
                Tables\Columns\TextColumn::make('signatures_valid_count')
                    ->label(__('maeppli.fields_short.signatures_valid_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('signatures_invalid_count')
                    ->label(__('maeppli.fields_short.signatures_invalid_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('weight_grams')
                    ->label(__('maeppli.fields_short.weight_grams'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('maeppli.created_at'))
                    ->dateTime('d.m.Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            $maeppli = Maeppli::create($data);

            Notification::make()
                ->success()
                ->title(__('maeppli.name') . ' created')
                ->body(__('maeppli.label') . ': ' . $maeppli->display_label)
                ->send();

            $this->form->fill();
            $this->dispatch('table-reloaded');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating ' . __('maeppli.name'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
