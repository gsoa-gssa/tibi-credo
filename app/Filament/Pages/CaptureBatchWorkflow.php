<?php

namespace App\Filament\Pages;

use App\Models\Maeppli;
use App\Exceptions\MatchBatchException;
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
                    ->live(onBlur: true)
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
                Forms\Components\TextInput::make('signatures_invalid_count')
                    ->label(__('maeppli.fields.signatures_invalid_count'))
                    ->numeric()
                    ->live(onBlur: true)
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
                Forms\Components\TextInput::make('sheets_count')
                    ->label(__('maeppli.fields.sheets_count'))
                    ->numeric()
                    ->live(onBlur: true)
                    ->minValue(1)
                    ->maxValue(1000)
                    ->helperText(__('maeppli.sheets_count_helper'))
                    ->columnSpan(2),
                Forms\Components\TextInput::make('weight_grams')
                    ->label(__('batch.fields.weight_grams'))
                    ->numeric()
                    ->live(onBlur: true)
                    ->columnSpan(2)
                    ->minValue(0)
                    ->maxValue(5000)
                    ->helperText(__('maeppli.helpers.weight_grams'))
                    ->hidden(function (Get $get, $record) {
                        if ($record !== null) {
                            return false;
                        }
                        $valid = $get('signatures_valid_count');
                        $invalid = $get('signatures_invalid_count');
                        $sigs = ((int) $valid) + ((int) $invalid);
                        if (!is_numeric($sigs)) {
                            return true;
                        }
                        return ((int) $sigs) < 100;
                    })
                    ->required(fn ($record) => $record === null),
                Forms\Components\Checkbox::make('suspect_values')
                    ->label(__('maeppli.fields.suspect_values'))
                    ->dehydrated(false)
                    ->required()
                    ->hidden(function (Get $get) {
                        $valid = $get('signatures_valid_count');
                        $invalid = $get('signatures_invalid_count');
                        $sheets = $get('sheets_count');
                        $weight = $get('weight_grams');
                        $valid = (int) $valid;
                        $invalid = (int) $invalid;
                        $sheets = (int) $sheets;
                        $expected_weight = $sheets ? $sheets * 5 : $valid / 2 * 5;
                        $ratio_valid_invalid = $invalid > 0 ? $valid / $invalid : 0.9;
                        $ratio_valid_sheets = $sheets > 0 ? $valid / $sheets : 2;
                        $validity_suspect = ($ratio_valid_invalid < 1 || $ratio_valid_invalid > 10) && $valid > 10;
                        $sheets_suspect = $ratio_valid_sheets > 10 || ($ratio_valid_sheets > 5 && $valid > 50) || ($ratio_valid_sheets > 3 && $valid > 200);
                        $weight_suspect = !empty($weight) && ($weight < ($expected_weight * 0.8) || $weight > ($expected_weight * 1.2));
                        return !($validity_suspect || $sheets_suspect || $weight_suspect);
                    })
                    ->columnSpan(2)
                    ->helperText(__('maeppli.helpers.suspect_values')),
                Forms\Components\Checkbox::make('no_matching')
                    ->label(__('maeppli.fields.no_matching'))
                    ->columnSpan(2)
                    ->hidden(fn () => !auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    ->helperText(__('maeppli.helpers.no_matching')),
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
                ->title(__('pages.captureBatchWorkflow.notifications.maeppli_created'))
                ->body($maeppli->getDisplayLabelAttribute())
                ->send();

            $this->form->fill();
            $this->dispatch('table-reloaded');
        } catch (MatchBatchException $e) {
            Notification::make()
                ->danger()
                ->title(__('maeppli.match_batch_exception.title'))
                ->body(__('maeppli.match_batch_exception.body'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating ' . __('maeppli.name'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
