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

class CreateBatchWorkflow extends Page implements HasForms
{
    protected static ?string $model = Batch::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 1;

    public ?array $initiateData = [];

    public ?array $sheetsData = [];
    public bool $addSheetsManually = false;

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
                    ->required()
                    ->relationship('commune', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make("numberOfSheets")
                    ->label(__("pages.createBatchWorkflow.numberOfSheets"))
                    ->required(),
            ])
            ->columns(2)
            ->statePath('initiateData')
            ->model(Batch::class);
    }

    public function addSheetsManuallyForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make("sheet_ids")
                    ->label(__("pages.createBatchWorkflow.sheet_ids"))
                    ->required()
                    ->rows(10)
                    ->autosize(),
                Forms\Components\Textarea::make("problemSheets")
                    ->label(__("pages.createBatchWorkflow.problemSheets"))
                    ->disabled()
                    ->rows(10)
                    ->autosize(),
            ])
            ->columns(2)
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
                ->label(__("pages.createBatchWorkflow.addSheetsManually.submit"))
                ->submit("addSheetsManuallySubmit")
        ];
    }

    public function initiateBatch()
    {
        $data = $this->initiateData;
        $commune = \App\Models\Commune::find($data['commune_id']);
        // Find all unassigned sheets that are older than 24 hours
        $unassignedSheets = $commune->sheets()->whereNull('batch_id')->whereDate('created_at', '<', now()->subDay())->get();
        if ($unassignedSheets->count() == $data['numberOfSheets']) {
            $batch = Batch::create([
                'commune_id' => $data['commune_id'],
                'status' => 'pending'
            ]);
            $unassignedSheets->each(function ($sheet) use ($batch) {
                $sheet->update(['batch_id' => $batch->id]);
            });
            return redirect()->route('filament.app.resources.batches.view', $batch);
        } else {
            $this->addSheetsManually = true;
        }
    }

    public function addSheetsManuallySubmit()
    {
        $data = $this->sheetsData;
        $manualSheets = explode("\n", $data['sheet_ids']);
        $manualSheets = array_map('trim', $manualSheets);
        $manualSheets = array_filter($manualSheets);
        $this->sheetsData["sheet_ids"] = implode("\n", $manualSheets);
        $this->sheetsData["problemSheets"] = "";
        foreach($manualSheets as $key => $manualSheet) {
            $cleanedSheet = trim($manualSheet);
            // Check if $manualSheet only contains numbers
            if (!ctype_digit($cleanedSheet)) {
                $manualSheets[$key] = "VOX–" . substr($cleanedSheet, strcspn($cleanedSheet, '0123456789'));
            } else {
                $manualSheets[$key] = $cleanedSheet;
            }
        }
        // Create SheetStatus Array containing the manual sheets as string extended with " - OK"
        $sheetsInformation = array_fill_keys($manualSheets, NULL);
        if (count($manualSheets) != $this->initiateData['numberOfSheets']) {
            Notification::make()
                ->title(__('pages.createBatchWorkflow.addSheetsManually.numberMismatch.title'))
                ->body(__('pages.createBatchWorkflow.addSheetsManually.numberMismatch.body'))
                ->danger()
                ->seconds(15)
                ->send();
            return;
        }
        $sheets = \App\Models\Sheet::whereIn('label', $manualSheets)->get();
        if ($sheets->count() != count($manualSheets))
        {
            $problemSheets = array_diff($manualSheets, $sheets->pluck('label')->toArray());
            foreach ($problemSheets as $problemSheet) {
                $sheetsInformation[$problemSheet] = $problemSheet . __("pages.createBatchWorkflow.addSheetsManually.sheetStatus.notFound");
            }
        }

        if ($sheets->where("batch_id", null)->count() != count($manualSheets))
        {
            $problemSheets = $sheets->where("batch_id", "!=", null)->pluck('label')->toArray();
            foreach ($problemSheets as $problemSheet) {
                $sheetsInformation[$problemSheet] = $problemSheet . __("pages.createBatchWorkflow.addSheetsManually.sheetStatus.notUnassigned");
            }
        }

        if ($sheets->where("commune_id", $this->initiateData['commune_id'])->count() != count($manualSheets))
        {
            $problemSheets = $sheets->where("commune_id", "!=", $this->initiateData['commune_id'])->pluck('label')->toArray();
            foreach ($problemSheets as $problemSheet) {
                $sheetsInformation[$problemSheet] = $problemSheet . __("pages.createBatchWorkflow.addSheetsManually.sheetStatus.notInCommune");
            }
        }


        if (!empty(array_filter($sheetsInformation))) {
            // Add " - OK" to the sheets that are OK
            array_walk($sheetsInformation, function (&$value, $key) {
                if (is_null($value)) {
                    $value = $key . __("pages.createBatchWorkflow.addSheetsManually.sheetStatus.ok");
                }
            });

            $this->sheetsData['problemSheets'] = implode("\n", $sheetsInformation);
        } else {
            $batch = Batch::create([
                'commune_id' => $this->initiateData['commune_id'],
                'status' => 'pending'
            ]);
            $sheets->each(function ($sheet) use ($batch) {
                $sheet->update(['batch_id' => $batch->id]);
            });
            return redirect()->route('filament.app.resources.batches.view', $batch);
        }

    }

    protected function fillForms(): void
    {
        $this->initiateBatchForm->fill();
        $this->addSheetsManuallyForm->fill();
    }

    protected static string $view = 'filament.pages.create-batch-workflow';
}
