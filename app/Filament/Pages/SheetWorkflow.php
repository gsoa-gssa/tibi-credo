<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use Filament\Forms;
use Filament\Tables;
use App\Models\Sheet;
use App\Models\Source;
use App\Models\Zipcode;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\File;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Support\Htmlable;

class SheetWorkflow extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;
    protected static ?string $model = Sheet::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 0;

    public function getTitle(): string | Htmlable
    {
        return __('pages.sheetWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.sheetWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.sheetWorkflow.navigationGroup');
    }

    public $vox;
    public $label;
    public $vox_label;
    public $source_id;
    public $signatureCount = 0;
    public $commune_id;
    public $sheet;

    public $contacts = [];

    /**
     * On Mount
     */
    public function mount()
    {
        $this->label = auth()->user()->getNextSheetLabel();
        $lastsheet = Sheet::where('user_id', auth()->id())->orderBy('id', 'desc')->first();
        // Check if last sheet exists and it isnt older than 1 hour
        if ($lastsheet && $lastsheet->created_at->diffInHours(now()) < 1) {
            if ($lastsheet->vox) {
                $this->vox = true;
                $this->source_id = 1;
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('vox')
                    ->label(__('input.label.sheetWorkflow.vox'))
                    ->inline(false)
                    ->helperText(__('input.helper.sheetWorkflow.vox'))
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->live()
                    ->id('vox')
                    ->autofocus(false)
                    ->default("on"),
            \HasanAhani\FilamentOtpInput\Components\OtpInput::make('label')
                ->label(__('input.label.sheetWorkflow.label'))
                ->helperText(__('input.helper.sheetWorkflow.label'))
                ->numberInput(6)
                ->visible(fn(Get $get) => !$get("vox"))
                ->required(),
            Forms\Components\TextInput::make('vox_label')
                ->label(__('input.label.sheetWorkflow.label'))
                ->helperText(__('input.helper.sheetWorkflow.label'))
                ->prefix('VOX – ')
                ->default('')
                ->autofocus(fn(Get $get) => $get("vox"))
                ->visible(fn(Get $get) => $get("vox"))
                ->required(),
            Forms\Components\Select::make("source_id")
                ->required()
                ->options(function () {
                    $sources = Source::all();
                    $options = [];
                    foreach ($sources as $source) {
                        if ($source->shortcut) {
                            $options[$source->id] = $source->shortcut . " " . $source->code;
                        } else {
                            $options[$source->id] = $source->code;
                        }
                    }
                    return $options;
                })
                ->preload()
                ->searchDebounce(200)
                ->label(__('input.label.sheetWorkflow.source'))
                ->helperText(__('input.helper.sheetWorkflow.source'))
                // Disable if vox
                ->disabled(fn(Get $get) => $get("vox"))
                ->autofocus(fn(Get $get) => !$get("vox"))
                ->searchable(),
            Forms\Components\TextInput::make('signatureCount')
                ->label(__('input.label.sheetWorkflow.signatureCount'))
                ->default(0)
                ->minValue(1)
                ->maxValue(10)
                ->required()
                ->helperText(__('input.helper.sheetWorkflow.signatureCount'))
                ->numeric(),
            Forms\Components\Select::make('commune_id')
                ->searchable()
                ->label(__('input.label.sheetWorkflow.commune'))
                ->helperText(__('input.helper.sheetWorkflow.commune'))
                ->getSearchResultsUsing(
                    function (string $search): array {
                        if (strlen($search) < 4) {
                            return [];
                        }
                        $zipcodes = Zipcode::where('code', 'like', "%$search%")->orWhere('name', 'like', "%$search%")->orderBy("number_of_dwellings", "desc")->get();
                        $results = [];
                        foreach ($zipcodes as $zipcode) {
                            $label = $zipcode->commune->name != $zipcode->name ? $zipcode->code . " " . $zipcode->commune->name . ' (' . __("input.helper.sheetWorkflow.place") . ": " . $zipcode->name . ')' : $zipcode->code . " " . $zipcode->commune->name;
                            $results[] = [
                                $zipcode->commune->id => $label,
                            ];
                        }
                        return $results;
                    })
                ->searchDebounce(200)
                ->preload()
                ->required(),
        ])
        ->columns(2);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Contact::query()->whereIn('id', $this->contacts))
            ->columns([
                Tables\Columns\TextColumn::make('firstname')
                    ->label(__("tables.columns.contacts.firstname"))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label(__("tables.columns.contacts.lastname"))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('street_no')
                    ->label(__("tables.columns.contacts.street_no"))
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                    Tables\Actions\Action::make('add')
                        ->label(__('tables.actions.contacts.add'))
                        ->form([
                            Forms\Components\TextInput::make('firstname')
                        ->label(__('input.label.contacts.firstname'))
                        ->required(),
                    Forms\Components\TextInput::make('lastname')
                        ->label(__('input.label.contacts.lastname'))
                        ->required(),
                    Forms\Components\TextInput::make('street_no')
                        ->label(__('input.label.contacts.street_no'))
                        ->required(),
                    Forms\Components\DatePicker::make('birthdate')
                        ->label(__('input.label.contacts.birthdate'))
                        ->required(),
                    Forms\Components\Select::make('zipcode_id')
                        ->label(__('input.label.contacts.zipcode'))
                        ->relationship('zipcode', 'code')
                        ->searchable()
                        ->searchDebounce(100)
                        ->required()
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('city', Zipcode::find($state)?->name))
                        ->live(),
                    Forms\Components\TextInput::make('city')
                        ->label(__('input.label.contacts.city'))
                        ->required()
                        ->maxLength(255),
                    ])
                    ->action(function (array $data): void {
                        $contact = Contact::create($data);
                        $this->contacts[] = $contact->id;
                    })
                    ->icon('heroicon-o-plus-circle')
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('store')
                ->label(__('forms.actions.contacts.store'))
                ->keyBindings(['mod+s'])
                ->submit('store')
        ];
    }

    public function getHeaderActions(): array
    {
        return [
        ];
    }

    public function store()
    {
        $this->validate();
        $data = $this->form->getState();
        $data['user_id'] = auth()->id();
        if (isset($data['vox']) && $data['vox']) {
            $data['label'] = "VOX–" . $data['vox_label'];
            unset($data['vox_label']);
        } else {
            $data['label'] = $data['label'];
            $data['vox'] = false;
        }

        $existingSheet = Sheet::where('label', $data['label'])->first();
        if ($existingSheet) {
            Notification::make()
                ->danger()
                ->seconds(15)
                ->title(__('notifications.sheetWorkflow.labelExists'))
                ->body(__('notifications.sheetWorkflow.labelExistsBody'))
                ->send();
            return;
        }

        $sheet = Sheet::create($data);
        if ($this->contacts) {
            $contacts = Contact::whereIn('id', $this->contacts)->get();
            $sheet->contacts()->saveMany($contacts);
        }
        Notification::make()
            ->success()
            ->seconds(15)
            ->title(__('notifications.sheetWorkflow.success'))
            ->send();
        $this->redirect(route('filament.app.pages.sheet-workflow'));
    }

    protected static string $view = 'filament.pages.sheet-workflow';
}
