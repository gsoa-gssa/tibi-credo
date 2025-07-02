<?php

namespace App\Filament\Pages;

use App\Models\Batch;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CreateBatchWorkflow extends Page implements HasForms
{
    protected static ?string $model = Batch::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 1;

    public ?array $initiateData = [];

    public ?array $sheetsData = null;

    public bool $pageTwo = false;

    public function getTitle(): string | Htmlable
    {
        return __('pages.createBatchWorkflowB.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.createBatchWorkflowB.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.createBatchWorkflowB.navigationGroup');
    }

    public function mount(): void
    {
        $this->fillForms();
    }

    protected function getForms(): array
    {
        return [
            'initiateBatchForm',
            'addSheetsManuallyForm'
        ];
    }
    public function initiateBatchForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make("commune_id")
                    ->required() # BUG this required is not working
                    ->relationship('commune', 'name')
                    ->preload()
                    ->disabled(fn () => $this->pageTwo)
                    ->searchable(),
            ])
            ->columns(1)
            ->statePath('initiateData')
            ->model(Batch::class);
    }

    public function addSheetsManuallyForm(Form $form): Form
    {
        if (!isset($this->initiateData['commune_id'])) {
            return $form->schema([]); // Return empty schema if not set
        }
        $commune_id = $this->initiateData['commune_id'];
        $commune = \App\Models\Commune::find($commune_id);
        // Check if commune is part of any address group
        if ($commune === null) {
            Notification::make()
                ->title(__('pages.createBatchWorkflowB.addSheetsManually.communeNotFound'))
                ->danger()
                ->send();
            return $form->schema([]); // Return empty schema if commune not found
        } else if ($commune->addressgroup !== 'none') {
            $addressGroup = $commune->addressgroup;
            $communes = \App\Models\Commune::where('addressgroup', $addressGroup)
                ->orderBy('name')
                ->get();
            $unassignedSheets = $communes->flatMap(function ($commune) {
                return $commune->sheets()->whereNull('batch_id')->orderBy('label')->get();
            })->unique('id')->sortBy('label');
        } else {
            $unassignedSheets = $commune->sheets()->whereNull('batch_id')->orderBy('label')->get();
        }
        # return a $form with a checkbox for each sheet, default checked
        # to do this map the unassigned sheets to checkboxes
        $checkboxes = $unassignedSheets->map(function ($sheet) {
            return Forms\Components\Checkbox::make("sheet_checkbox_{$sheet->id}")
                ->label($sheet->label . " (" . $sheet->commune->name . ")");
        });

        // TODO ugly workaround to avoid empty checkbox
        // for each unassigned sheet write default true to sheetsData
        if($this->sheetsData === null) {

            $this->sheetsData = $unassignedSheets->mapWithKeys(function ($sheet) {
                return ["sheet_checkbox_{$sheet->id}" => true];
            })->toArray();
        }

        return $form
            ->schema($checkboxes->toArray())
            ->columns(1)
            ->statePath('sheetsData');
    }

    public function initiateBatchActions(): array
    {
        return [
            \Filament\Actions\Action::make('initiateBatchActions')
                ->label(__("pages.createBatchWorkflow.initiateBatch.submit"))
                ->submit("initiateBatch")
        ];
    }

    public function addSheetsManuallyActions(): array
    {
        return [
            \Filament\Actions\Action::make('addSheetsManuallySubmit')
                ->label(__("pages.createBatchWorkflowB.addSheetsManually.submit"))
                ->submit("addSheetsManuallySubmit")
        ];
    }

    public function initiateBatch()
    {
        $this->pageTwo = true;
    }

    public function addSheetsManuallySubmit()
    {
        $data = $this->sheetsData;

        // check if data is empty or if no sheets are selected
        if (empty($data) || !collect($data)->contains(function ($value, $key) {
            return strpos($key, 'sheet_checkbox_') === 0 && $value === true;
        })) {
            // Show a notification if no sheets are selected
            Notification::make()
                ->title(__('pages.createBatchWorkflowB.addSheetsManually.noSheetsSelected'))
                ->danger()
                ->send();
            return;
        }

        // get all sheets that are checked
        $sheets = collect($data)
            ->filter(function ($value, $key) {
                return strpos($key, 'sheet_checkbox_') === 0 && $value === true;
            })
            ->map(function ($value, $key) {
                return (int) str_replace('sheet_checkbox_', '', $key);
            })
            ->map(function ($id) {
                return \App\Models\Sheet::find($id);
            });

        $batch = Batch::create([
            'commune_id' => $this->initiateData['commune_id'],
            'status' => 'pending'
        ]);

        $sheets->each(function ($sheet) use ($batch) {
            $sheet->update(['batch_id' => $batch->id]);
        });
        return redirect()->route('filament.app.resources.batches.view', $batch);
    }

    protected function fillForms(): void
    {
        $this->initiateBatchForm->fill();
        $this->addSheetsManuallyForm->fill();
    }

    protected static string $view = 'filament.pages.create-batch-workflow-b';
}
