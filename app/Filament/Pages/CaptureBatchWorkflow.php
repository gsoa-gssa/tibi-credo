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
                    Forms\Components\Wizard\Step::make('basicInfo')
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
                        ]),
                    Forms\Components\Wizard\Step::make('requirements')
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

                        ])
                        ->columns(2),
                    Forms\Components\Wizard\Step::make('sheetsInformation')
                        ->schema([
                            Forms\Components\TextInput::make('number_of_sheets')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_sheets'))
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(1000),
                            Forms\Components\TextInput::make('number_of_valid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_valid_signatures'))
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(1000),
                            Forms\Components\TextInput::make('number_of_invalid_signatures')
                                ->label(__('pages.captureBatchWorkflow.sheetsInformation.number_of_invalid_signatures'))
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(1000),
                        ])
                        ->columns(3),
                    Forms\Components\Wizard\Step::make('sheets')
                        ->schema($this->addSheetCheckboxes())
                        ->columns(3)
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
            $sections[] = Forms\Components\Section::make("Batch " . $batch->id . ' - ' . $sheets->count() . ' Bögen')
                ->schema([
                    Forms\Components\ViewField::make('toggle-all')
                        ->view('filament.forms.components.capture-batch-wizard.toggle-all')
                        ->columnSpanFull(),
                        ...$checkboxes
                    ])
                ->columns(2)
                ->collapsible();
        }
        return $sections;
    }

    public function captureBatch(): void
    {
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
}
