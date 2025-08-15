<?php

namespace App\Filament\Pages;

use Filament\Forms;
use App\Models\Sheet;
use App\Models\Commune;
use App\Models\Maeppli;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;

class CaptureBatchWorkflow extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

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
        $this->captureBatchWizard->fill();
    }

    public function getForms(): array
    {
        return [
            'captureBatchWizard',
        ];
    }

    public $data;
    public $basicsDone = false;
    public function captureBatchWizard(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make(__('pages.captureBatchWorkflow.step.basicInfo'))
                        ->schema([
                            Forms\Components\Select::make('commune')
                                ->label(__('pages.captureBatchWorkflow.selectCommuneForm.commune'))
                                ->options(Commune::all()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->live()
                                ->preload(),
                            Forms\Components\Radio::make('certificationType')
                                ->label(__('pages.captureBatchWorkflow.certificationTypeForm.certificationType'))
                                ->options([
                                    'individual' => __('pages.captureBatchWorkflow.certificationTypeForm.individual'),
                                    'collective' => __('pages.captureBatchWorkflow.certificationTypeForm.collective'),
                                ])
                                ->live()
                                ->columns(2)
                                ->required(),
                            Forms\Components\ViewField::make('certificationType')
                                ->view('filament.forms.components.capture-batch-wizard.certification-type')
                        ]),
                    Forms\Components\Wizard\Step::make(__('pages.captureBatchWorkflow.step.requirements'))
                        ->schema([
                            Forms\Components\ViewField::make('requirements-individual')
                                ->view('filament.forms.components.capture-batch-wizard.requirements-individual')
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->columnSpan(2),
                            Forms\Components\Checkbox::make("control_field")
                                ->label(__('pages.captureBatchWorkflow.requirements.control_field'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->required(),
                            Forms\Components\Checkbox::make("number_of_valid_signatures_individual")
                                ->label(__('pages.captureBatchWorkflow.requirements.number_of_valid_signatures'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->required(),
                            Forms\Components\Checkbox::make("certification_authority_information")
                                ->label(__('pages.captureBatchWorkflow.requirements.certification_authority_information'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->required(),
                            Forms\Components\Checkbox::make("seal_individual")
                                ->label(__('pages.captureBatchWorkflow.requirements.seal_individual'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->required(true),
                            Forms\Components\ViewField::make('requirements-collective')
                                ->view('filament.forms.components.capture-batch-wizard.requirements-collective')
                                ->columnSpan(2)
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective'),
                            Forms\Components\Checkbox::make("date")
                                ->label(__('pages.captureBatchWorkflow.requirements.date'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(true),
                            Forms\Components\Checkbox::make("city")
                                ->label(__('pages.captureBatchWorkflow.requirements.city'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("number_of_valid_signatures_collective")
                                ->label(__('pages.captureBatchWorkflow.requirements.number_of_valid_signatures_collective'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("signature_of_authorizers")
                                ->label(__('pages.captureBatchWorkflow.requirements.signature_of_authorizers'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("formal_position_of_authorizers")
                                ->label(__('pages.captureBatchWorkflow.requirements.formal_position_of_authorizers'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("seal_collective")
                                ->label(__('pages.captureBatchWorkflow.requirements.seal_collective'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("correct_name")
                                ->label(__('pages.captureBatchWorkflow.requirements.correct_name'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\Checkbox::make("date_of_publication")
                                ->label(__('pages.captureBatchWorkflow.requirements.date_of_publication'))
                                ->visible(fn (Get $get) => $get('certificationType') === 'collective')
                                ->required(),
                            Forms\Components\ViewField::make('requirements-individual-warning')
                                ->view('filament.forms.components.capture-batch-wizard.requirements-individual-warning')
                                ->visible(fn (Get $get) => $get('certificationType') === 'individual')
                                ->columnSpan(2),
                        ])
                        ->columns(2),
                    Forms\Components\Wizard\Step::make(__('pages.captureBatchWorkflow.step.sheetsInformation'))
                        ->schema([
                            Forms\Components\TextInput::make('number_of_sheets')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_sheets'))
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(5000),
                            Forms\Components\TextInput::make('number_of_valid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_valid_signatures'))
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(10000),
                            Forms\Components\TextInput::make('number_of_invalid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_invalid_signatures'))
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(10000),
                        ])
                        ->columns(3),
                    Forms\Components\Wizard\Step::make(__('pages.captureBatchWorkflow.step.sheetsSelection'))
                        ->schema($this->addSheetCheckboxes())
                        ->columns(3),
                    Forms\Components\Wizard\Step::make(__('pages.captureBatchWorkflow.step.review'))
                        ->schema([
                            Forms\Components\TextInput::make('number_of_sheets')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_sheets'))
                                ->disabled(),
                            Forms\Components\TextInput::make('number_of_valid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_valid_signatures'))
                                ->disabled(),
                            Forms\Components\TextInput::make('number_of_invalid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_invalid_signatures'))
                                ->disabled(),
                            Forms\Components\ViewField::make('selected_sheets_list')
                                ->view('filament.forms.components.capture-batch-wizard.selected-sheets-list')
                                ->columnSpanFull(),
                            Forms\Components\ViewField::make('number_of_signatures_warning')
                                ->view('filament.forms.components.capture-batch-wizard.number-of-signatures-warning'),
                            Forms\Components\Checkbox::make('confirm')
                                ->label(__('pages.captureBatchWorkflow.step.review.confirm'))
                                ->required()
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $selectedSheets = collect($this->data['sheets'] ?? [])->filter()->count();
                                            $claimedSheets = (int) ($this->data['number_of_sheets'] ?? 0);
                                            if ($selectedSheets !== $claimedSheets) {
                                                $fail(__('pages.captureBatchWorkflow.validation.sheets_count_mismatch', [
                                                    'claimed' => $claimedSheets,
                                                    'selected' => $selectedSheets,
                                                ]));
                                            }
                                        };
                                    },
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (!$value) {
                                                $fail('You must confirm before proceeding.');
                                            }
                                        };
                                    },
                                ]),
                        ])
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        Versand rückerfassen
                    </x-filament::button>
                BLADE)))
            ])
            ->statePath('data');
    }

    public function addSheetCheckboxes(): array
    {
        $commune = Commune::find($this->data['commune'] ?? null);
        $batches = $commune ? $commune->batches()->get() : collect();
        $sections = [];
        foreach ($batches as $batch) {
            $sheets = $batch->sheets->whereNull('maeppli_id')->sortBy('label');
            if ($sheets->isEmpty()) {
                continue; // Skip if there are no sheets in this batch
            }
            $checkboxes = [];
            foreach ($sheets as $sheet) {
                $checkboxes[] = Forms\Components\Checkbox::make("sheets.{$sheet->id}")
                    ->label($sheet->label)
                    ->default(true);
            }
            $sections[] = Forms\Components\Section::make("Batch " . $batch->id . ' - ' . $sheets->count() . ' Bögen - ' . $batch->countSignatures() . ' Unterschriften')
                ->schema([
                    Forms\Components\ViewField::make('toggle-all')
                        ->view('filament.forms.components.capture-batch-wizard.toggle-all')
                        ->columnSpanFull(),
                        ...$checkboxes
                    ])
                ->columns(2)
                ->collapsible();
        }

        if ($commune !== null) {
            // show sheets from that commune that are not in a batch
            $unbatchedSheets = $commune->sheets()->whereNull('batch_id');

            // if there are such sheets, create a new section
            if ($unbatchedSheets->exists()) {
                $sections[] = Forms\Components\Section::make("Noch nicht versendete Bögen - " . $unbatchedSheets->count() . " Bögen")
                    ->schema([
                        Forms\Components\ViewField::make('toggle-all')
                            ->view('filament.forms.components.capture-batch-wizard.toggle-all')
                            ->columnSpanFull(),
                        ...$unbatchedSheets->get()->map(function ($sheet) {
                            return Forms\Components\Checkbox::make("sheets.{$sheet->id}")
                                ->label($sheet->label)
                                ->default(true);
                        })
                    ])
                    ->columns(2)
                    ->collapsible();
            }

            // show sheets from that commune that are in a batch not included in $batches
            $wrongBatchedSheets = $commune->sheets()->whereNotIn('batch_id', $batches->pluck('id'));

            if ($wrongBatchedSheets->exists()) {
                $sections[] = Forms\Components\Section::make("An andere Gemeinden verschickte Bögen - " . $wrongBatchedSheets->count() . " Bögen")
                    ->schema([
                        Forms\Components\ViewField::make('toggle-all')
                            ->view('filament.forms.components.capture-batch-wizard.toggle-all')
                            ->columnSpanFull(),
                        ...$wrongBatchedSheets->get()->map(function ($sheet) {
                            return Forms\Components\Checkbox::make("sheets.{$sheet->id}")
                                ->label($sheet->label)
                                ->default(true);
                        })
                    ])
                    ->columns(2)
                    ->collapsible();
            }
        }
        return $sections;
    }

    public function captureBatch(): void
    {
        $this->validate();

        // if data wasn't reviewed refuse
        if (!$this->data['confirm']) {
            $this->addError('confirm', __('validation.confirm'));
            return;
        }

        $commune = Commune::find($this->data['commune']);
        $attributes = [
            'label' => $commune->canton->label . " – " . sprintf('%04u', max(1, Maeppli::max('id') + 1)),
            'commune_id' => $commune->id,
            'sheets_count' => $this->data['number_of_sheets'],
            'sheets_valid_count' => $this->data['number_of_valid_signatures'],
            'sheets_invalid_count' => $this->data['number_of_invalid_signatures']
        ];
        $maeppli = Maeppli::create($attributes);
        $sheets = collect($this->data['sheets'] ?? []);
        foreach ($sheets as $sheetId => $checked) {
            if ($checked) {
                $sheet = Sheet::find($sheetId)->update([
                    'maeppli_id' => $maeppli->id,
                ]);
            }
        }
        redirect()->route('filament.app.resources.maepplis.view', $maeppli->id);

    }

    protected static string $view = 'filament.pages.capture-batch-workflow';

    public function sheetsLabels()
    {
        $sheets = collect($this->data['sheets'] ?? [])->filter();
        // map sheet ids to labels
        $sheets = $sheets->map(function ($item, $key) {
            // get sheet object and label
            $sheet = Sheet::find($key);
            return $sheet ? $sheet->label : 'Unknown';
        });
        // sort
        return $sheets->sort()->values();
    }

    public function checkSignatureCount()
    {
        $returned = $this->data['number_of_valid_signatures'] + $this->data['number_of_invalid_signatures'];
        // map sheet ids to number of signatures on sheet
        $sheets = collect($this->data['sheets'] ?? [])->filter();
        $sheets = $sheets->map(function ($item, $key) {
            $sheet = Sheet::find($key);
            return $sheet->signatureCount;
        });
        $total = $sheets->sum();
        return $total === $returned;
    }
}
