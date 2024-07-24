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
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Support\Htmlable;

class SheetWorkflow extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;
    protected static ?string $model = Sheet::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Workflows';
    protected static ?int $navigationSort = 0;

    public function getTitle(): string | Htmlable
    {
        return __('Sheet Workflow');
    }

    public static function getNavigationLabel(): string
    {
        return __('Sheet Workflow');
    }
    public $label = '123456';
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
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            \HasanAhani\FilamentOtpInput\Components\OtpInput::make('label')
                ->label('Sheet Number')
                ->numberInput(6)
                ->default('test')
                ->required(),
            Forms\Components\Select::make("source_id")
                ->required()
                ->options(Source::all()->pluck("code", 'id'))
                ->label('Source')
                ->helperText(__('The three letters on the top left of the signature sheet.'))
                ->searchable(),
            Forms\Components\TextInput::make('signatureCount')
                ->label('Signature Count')
                ->default(0)
                ->required()
                ->helperText(__('The number of people who have signed the initiative on this sheet.'))
                ->numeric(),
            Forms\Components\Select::make('commune_id')
                ->searchable()
                ->label('Commune ZIP')
                ->helperText(__('The ZIP Code on the sheet. Select from possible options. If it\'s not clear, press skip and administrators will take care of the sheet manually.'))
                ->getSearchResultsUsing(
                    function (string $search): array {
                        $zipcodes = Zipcode::where('code', 'like', "%$search%")->limit(10)->get();
                        $results = [];
                        foreach ($zipcodes as $zipcode) {
                            $results[] = [
                                $zipcode->commune->id => $zipcode->commune->name . ' (' . $zipcode->name . ')',
                            ];
                        }
                        return $results;
                    })
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
                    ->label(__("tables.contacts.firstname"))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label(__("tables.contacts.lastname"))
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add')
                    ->label(__('Add Contact'))
                    ->form([
                        Forms\Components\TextInput::make('firstname')
                            ->label(__('First Name'))
                            ->required(),
                        Forms\Components\TextInput::make('lastname')
                            ->label(__('Last Name'))
                            ->required(),
                        Forms\Components\TextInput::make('street_no')
                            ->label(__('Street No'))
                            ->required(),
                        Forms\Components\DatePicker::make('birthdate')
                            ->label(__('Birthdate'))
                            ->required(),
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
                ->label(__('Save'))
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

        $existingSheet = Sheet::where('label', $data['label'])->first();
        if ($existingSheet) {
            Notification::make()
                ->danger()
                ->seconds(15)
                ->title(__('Sheet Already Exists, Skipping'))
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
            ->title('Sheet Created')
            ->send();
        $this->redirect(route('filament.app.pages.sheet-workflow'));
    }

    protected static string $view = 'filament.pages.sheet-workflow';
}
