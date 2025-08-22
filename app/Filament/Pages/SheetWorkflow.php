<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Filament\Resources\ContactResource;
use Filament\Forms;
use Filament\Tables;
use App\Models\Sheet;
use App\Models\Source;
use App\Models\Commune;
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
  protected static string $view = 'filament.pages.sheet-workflow';
  protected static ?string $navigationIcon = 'heroicon-o-document-plus';
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
  
  public $label;

  public $srcAndCount = '';
  public $source_id;
  public $signatureCount = 0;

  public $zipcode = null;
  public $place = '';
  public $commune_id;

  public $sheet;
  
  public $contacts = [];

  public $placeHelperText = '';
  public $communeHelperText = '';
  
  /**
  * On Mount
  */
  public function mount()
  {
    $this->label = auth()->user()->getNextSheetLabel();
    $lastsheet = Sheet::where('user_id', auth()->id())->orderBy('id', 'desc')->first();
  }
  
  public function form(Form $form): Form
  {
    return $form->schema([
      Forms\Components\TextInput::make('label')
        ->label(__('sheet.fields.label'))
        ->live()
        ->required()
        ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) { 
          $state = $component->getState();
          $state = preg_replace('/\s+/', '', $state);
          if (preg_match('/^vox/i', $state)) {
            if (preg_match('/^vox(\d)/i', $state, $matches)) {
              $state = 'VOX-' . substr($state, 3);
            }
            $state = 'VOX' . substr($state, 3);
          }
          $component->state($state);
          $livewire->resetErrorBag($component->getStatePath());

          if (!self::isLabelValid($state)) {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.label.regex', ['label' => $state]));
          }

          if (!self::isLabelAvailable($state)) {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.label.unavailable', ['label' => $state]));
          }
        }),
      Forms\Components\Group::make([
        Forms\Components\TextInput::make('srcAndCount')
          ->label(__('pages.sheetWorkflow.srcAndCount'))
          ->dehydrated(false)
          ->live()
          ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) { 
            $state = $component->getState();
            $state = preg_replace('/\s+/', '', $state);
            $state = strtoupper($state);
            $component->state($state);

            $livewire->resetErrorBag($component->getStatePath());

            if (preg_match('/^([A-Za-z]*)(\d+)$/', $state, $matches)) {
              $letters = $matches[1];
              $digits = $matches[2];

              if (empty($this->source_id) && empty($letters)) {
                $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.source_id.empty', ['srcAndCount' => $state]));
              }

              if (!empty($letters)) {
                $source = Source::where('code', 'LIKE', $letters . '%')->first();
                if ($source) {
                  $livewire->source_id = $source->id;
                } else {
                  $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.source_id.not_found', ['srcAndCount' => $state]));
                }
              }

              $signatureCount = (int) $digits;
              $livewire->signatureCount = $signatureCount;
              if ($signatureCount > 15 || $signatureCount < 1) {
                $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.signatureCount.range', ['signatureCount' => $signatureCount]));
              }              
            } else {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.srcAndCount.structure', ['srcAndCount' => $state]));
            }
          })
          ->columnSpan(3),
        Forms\Components\Select::make('source_id')
          ->label(__('source.name'))
          ->required()
          ->validationMessages([
            'required' => __('pages.sheetWorkflow.validation.source_id.empty', ['srcAndCount' => $this->srcAndCount]),
          ])
          ->options(Source::all()->pluck('code', 'id'))
          ->disabled(true),
        Forms\Components\TextInput::make('signatureCount')
          ->label(__('sheet.fields.signatureCountShort'))
          ->required()
          ->validationMessages([
            'required' => __('pages.sheetWorkflow.validation.signatureCount.required'),
          ])
          ->disabled(true),
      ])
      ->columns(2),
      Forms\Components\Group::make([
        Forms\Components\TextInput::make('zipcode')
          ->label(__('zipcode.fields.code'))
          ->dehydrated(false)
          ->live()
          ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) { 
            $state = $component->getState();

            $livewire->resetErrorBag($component->getStatePath());
            $livewire->resetErrorBag('place');
            $livewire->resetErrorBag('commune_id');

            if (!preg_match('/^\d{4}$/', $state)) {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.zipcode.format', ['zipcode' => $state]));
              return;
            }

            $zipcodes = Zipcode::where('code', $state)->get();
            $zipcode = $zipcodes->first();
            if (!$zipcode) {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.zipcode.not_found', ['zipcode' => $state]));
              return;
            }

            $livewire->commune_id = $zipcode->commune->id;

            $communeIds = $zipcodes->pluck('commune_id')->unique();
            $communeNames = $zipcodes->pluck('commune.name')->unique();
            $places = $zipcodes->pluck('name')->unique();
            $places = $places->merge($communeNames)->unique();
            
            if ($communeNames->count() > 1) {
              // $component->getContainer()->getComponent('place')->helperText(__('pages.sheetWorkflow.validation.place.multiple', ['zipcode' => $state, 'places' => $places->implode(', ')]));
              $livewire->placeHelperText = __('pages.sheetWorkflow.validation.place.multiple', ['zipcode' => $state, 'places' => $places->implode(', ')]);
              $livewire->communeHelperText = __('pages.sheetWorkflow.validation.commune.multiple_per_zipcode', ['zipcode' => $state, 'communes' => $communeNames->implode(', ')]);
              
              $livewire->place = '';

            } else {
              $livewire->place = $communeNames->first();
              $livewire->placeHelperText = __('pages.sheetWorkflow.validation.place.determined', ['zipcode' => $state, 'place' => $livewire->place]);
              $livewire->communeHelperText = __('pages.sheetWorkflow.validation.commune.determined', ['zipcode' => $state, 'commune' => $communeNames->first()]);
            }

          })
          ->numeric()
          ->minValue(1000)
          ->maxValue(9999),
        Forms\Components\TextInput::make('place')
          ->label(__('pages.sheetWorkflow.placeOrCommune'))
          ->dehydrated(false)
          ->helperText(fn () => $this->placeHelperText)
          ->live()
          ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) { 
            $state = $component->getState();

            $zipcodes = Zipcode::where('code', $livewire->zipcode)->get();
            $communeNames = $zipcodes->pluck('commune.name')->unique();
            $places = $zipcodes->pluck('name')->unique();
            $places = $places->merge($communeNames)->unique();

            $filteredPlaces = $places->filter(function ($placeName) use ($state) {
              // Lowercase both arguments for case-insensitive comparison
              return stripos(mb_strtolower($placeName), mb_strtolower($state)) === 0;
            });

            if ($filteredPlaces->count() === 1) {
              $component->state($filteredPlaces->first());

              $communes = Zipcode::where('code', $livewire->zipcode)
                ->where('name', $filteredPlaces->first())
                ->orWhereHas('commune', function ($query) use ($filteredPlaces) {
                  $query->where('name', $filteredPlaces->first());
                })
                ->get()
                ->pluck('commune_id')
                ->unique();

              if ($communes->count() === 1) {
                $livewire->commune_id = $communes->first();
                $livewire->resetErrorBag('commune_id');
                $livewire->placeHelperText = '';
                $commune_name = Commune::find($livewire->commune_id)->name;
                $livewire->communeHelperText = __('pages.sheetWorkflow.validation.commune.determined_zip_place', ['zipcode' => $livewire->zipcode, 'place' => $livewire->place, 'commune' => $commune_name]);
              } else {
                $livewire->addError('commune_id', __('pages.sheetWorkflow.validation.commune_id.multiple', ['zipcode' => $livewire->zipcode, 'place' => $communes->first(), 'communes' => $communes->implode(', ')]));
              }
            }
          })
          ->columnSpan(2),
        Forms\Components\Select::make('commune_id')
          ->label(__('commune.name'))
          ->required()
          ->validationMessages([
            'required' => __('pages.sheetWorkflow.validation.commune_id.required'),
          ])
          ->helperText(fn () => $this->communeHelperText)
          ->options(Commune::all()->pluck('name', 'id'))
          ->disabled(true)
          ->columnSpan(3),
      ])
      ->columns(3),
    ])
    ->columns(3);
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
            Forms\Components\Group::make(ContactResource::getFormSchema(true))
            ->columns(3),
          ])
          ->action(function (array $data): void {
            $contact = Contact::create($data);
            $this->contacts[] = $contact->id;
          })
          ->icon('heroicon-o-plus-circle'),
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
    $data = $this->form->getState();
    $data['signatureCount'] = $this->signatureCount;
    $data['commune_id'] = $this->commune_id;
    $data['source_id'] = $this->source_id;
    $data['user_id'] = auth()->id();

    $valid = self::isLabelValid($data['label']);
    $valid = $valid && self::isLabelAvailable($data['label']);

    if (!$valid) {
      Notification::make()
      ->danger()
      ->title(__('pages.sheetWorkflow.notification.invalid'))
      ->body(__('pages.sheetWorkflow.notification.invalidBody'))
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
    
    $lastSource = $this->source_id;
    $lastCommune = $this->commune_id;
    $this->form->fill();
    $this->label = auth()->user()->getNextSheetLabel();
    $this->commune_id = $lastCommune;
    $this->source_id = $lastSource;
    $this->contacts = [];
    $this->dispatch('focus', field: 'srcAndCount');
    $this->resetTable();
  }      
  
  public static function isLabelValid(?string $label): bool
  {
    return !$label || preg_match('/^(VOX-)?[0-9]+[a-zA-Z]?$/', $label) === 1;
  }

  public static function isLabelAvailable(?string $label): bool
  {
    return !Sheet::where('label', $label)->exists();
  }
}