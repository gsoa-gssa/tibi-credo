<?php

namespace App\Filament\Pages;

use App\Models\Batch;
use App\Models\Commune;
use App\Models\Sheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

class CreateBatchWorkflow extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $model = Batch::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 1;

    public ?array $data = [
        'commune_id' => null,
        'sheets' => [],
        'confirm' => false,
    ];

    public function getTitle(): string | Htmlable
    {
        return __('pages.createBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.createBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.createBatchWorkflow.navigationGroup');
    }

    public function mount(): void
    {
        $this->createBatchWizard->fill();
    }

    protected function getForms(): array
    {
        return [
            'createBatchWizard',
        ];
    }

    public function createBatchWizard(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make(__('commune.name'))
                        ->schema([
                            Forms\Components\Select::make("commune_id")
                                ->label(__('pages.createBatchWorkflow.selectCommune'))
                                ->required()
                                ->options(Commune::all()->mapWithKeys(function ($commune) {
                                    return [$commune->id => $commune->name . ' ' . ($commune->canton->label ?? '')];
                                }))
                                ->autofocus()
                                ->required()
                                ->validationMessages([
                                    'required' => __('pages.createBatchWorkflow.validation.select_commune'),
                                ])
                                ->preload()
                                ->searchable()
                                ->live(),
                        ]),
                    Forms\Components\Wizard\Step::make(__('pages.createBatchWorkflow.step.sheets'))
                        ->schema($this->getSheetSelectionSchema()),
                    Forms\Components\Wizard\Step::make(__('pages.createBatchWorkflow.step.review'))
                        ->schema([
                            Forms\Components\Checkbox::make('confirm')
                                ->label(__('pages.createBatchWorkflow.review.confirm'))
                                ->required()
                                ->validationMessages([
                                    'required' => __('pages.createBatchWorkflow.validation.confirm'),
                                ])
                                ->live()
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $selectedSheets = collect($this->data['sheets'] ?? [])->filter()->count();
                                            if ($selectedSheets === 0) {
                                                $fail(__('pages.createBatchWorkflow.validation.no_sheets_selected'));
                                            }
                                        };
                                    },
                                ]),
                            ]),
                    ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        {{ __('pages.createBatchWorkflow.submit') }}
                    </x-filament::button>
                BLADE)))
            ])
            ->statePath('data')
            ->model(Batch::class);
    }

    protected function getSheetSelectionSchema(): array
    {
        $commune_id = $this->data['commune_id'];
        $commune = Commune::find($commune_id);
        
        if ($commune === null) {
            return [
                Forms\Components\Placeholder::make('commune_not_found')
                    ->label(__('pages.createBatchWorkflowB.communeNotFound'))
                    ->columnSpanFull()
            ];
        }

        // Check if commune is part of any address group
        if ($commune->addressgroup !== 'none') {
            $addressGroup = $commune->addressgroup;
            $communes = Commune::where('addressgroup', $addressGroup)
                ->orderBy('name')
                ->get();
            $unassignedSheets = $communes->flatMap(function ($commune) {
                return $commune->sheets()->whereNull('batch_id')->orderBy('label')->get();
            })->unique('id')->sortBy('label');
        } else {
            $unassignedSheets = $commune->sheets()->whereNull('batch_id')->orderBy('label')->get();
        }

        if ($unassignedSheets->isEmpty()) {
            return [
                Forms\Components\Placeholder::make('no_sheets_available')
                    ->label(__('pages.createBatchWorkflow.noSheetsAvailable'))
                    ->columnSpanFull()
            ];
        }

        $checkboxes = $unassignedSheets->map(function ($sheet) {
            return Forms\Components\Checkbox::make("sheets.{$sheet->id}")
                ->label($sheet->getLabel() . " (" . $sheet->commune->name . ")")
                ->default(true);
        })->toArray();

        // TODO ugly workaround to avoid empty checkbox
        // for each unassigned sheet write default true to data['sheets']
        if (!isset($this->data['sheets']) || empty($this->data['sheets'])) {
            $this->data['sheets'] = $unassignedSheets->mapWithKeys(function ($sheet) {
                return [$sheet->id => true];
            })->toArray();
        }

        $lastBatch = Batch::where('commune_id', $commune->id)
            ->orderByDesc('created_at')
            ->first();

        if ($lastBatch) {
            array_unshift(
                $checkboxes,
                Forms\Components\Placeholder::make('last_batch_info')
                    ->label(__('pages.createBatchWorkflow.lastBatch.lastBatchDate', [
                        'date' => $lastBatch->created_at->format('Y-m-d H:i'),
                    ]))
                    ->columnSpanFull()
                    ->content(__('pages.createBatchWorkflow.lastBatch.infoExists'))
            );
        } else {
            array_unshift(
                $checkboxes,
                Forms\Components\Placeholder::make('no_previous_batch_info')
                    ->label(__('pages.createBatchWorkflow.lastBatch.noPreviousBatch'))
                    ->columnSpanFull()
                    ->content(__('pages.createBatchWorkflow.lastBatch.infoNone'))
            );
        }

        return $checkboxes;
    }

    public function createBatch(): void
    {
        $this->validate();

        if (!$this->data['confirm']) {
            $this->addError('confirm', __('validation.confirm'));
            return;
        }

        $selectedSheets = collect($this->data['sheets'] ?? [])->filter();
        
        if ($selectedSheets->isEmpty()) {
            Notification::make()
                ->title(__('pages.createBatchWorkflow.validation.no_sheets_selected'))
                ->danger()
                ->send();
            return;
        }

        $batch = Batch::create([
            'commune_id' => $this->data['commune_id'],
            'status' => 'pending'
        ]);

        foreach ($selectedSheets as $sheetId => $checked) {
            if ($checked) {
                $sheet = Sheet::find($sheetId);
                if ($sheet) {
                    $sheet->update(['batch_id' => $batch->id]);
                }
            }
        }

        redirect()->route('filament.app.resources.batches.view', $batch);
    }

    public function sheetsLabels()
    {
        $sheets = collect($this->data['sheets'] ?? [])->filter();
        return $sheets->map(function ($item, $key) {
            $sheet = Sheet::find($key);
            return $sheet ? $sheet->getLabel() : 'Unknown';
        })->sort()->values();
    }

    protected static string $view = 'filament.pages.create-batch-workflow';
}
