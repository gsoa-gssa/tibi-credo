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
    $sources = Source::all()->pluck('code', 'id')->toArray();
    return $form->schema([
      Forms\Components\TextInput::make('label')
        ->label(__('sheet.fields.label'))
        ->live()
        ->required()
        ->extraAttributes([
          'x-data' => '{}',
          'x-on:input' => '
              let text = $event.target.value.replace(/\s+/g, "");
              console.log("Old: " + text);
              if (/^[a-zA-Z]+$/.test(text)) {
                text = text.toUpperCase();
              }
              if (/^VOX(\d)/.test(text)) {
                text = "VOX-" + text.substring(3);
              } else if (/^VO(\d)/.test(text)) {
                text = "VOX-" + text.substring(2);
              } else if (/^V(\d)/.test(text)) {
                text = "VOX-" + text.substring(1);
              }
              $event.target.value = text;
              $wire.set("label", text);
              console.log("New: " + text);
          '
        ])
        ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) { 
          $state = $component->getState();
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
          ->extraAttributes([
            'x-data' => '{ sources: ' . \Illuminate\Support\Js::from($sources) . ' }',
            'x-on:input' => '
                let text = $event.target.value;
                console.log("Original: " + text);
                text = text.replace(/\s+/g, "");
                text = text.toUpperCase();

                // if there is digits followed by a letter, set signature count to digits
                let match = text.match(/^(\d+)([a-zA-Z].+)$/);
                if (match) {
                  let count = parseInt(match[1]);
                  // only set if count is reasonable, i.e. between 1 and 12
                  if (count >= 1 && count <= 12) {
                    $wire.set("signatureCount", count);
                  }
                  source_part = match[2];
                  console.log("Extracted count: " + count + ", source part: " + source_part);
                  console.log("Sources are: " + JSON.stringify(sources));
                  console.log("Have " + Object.keys(sources).length + " sources");
                  // filter sources object by whether code starts with text
                  sourcesCandidates = Object.entries(sources).filter(([id, code]) => code.startsWith(source_part));
                  if (sourcesCandidates.length === 1) {
                    console.log("Unique source match: " + sourcesCandidates);
                    $wire.set("source_id", sourcesCandidates[0][0]);
                  } else if (sourcesCandidates.length > 1) {
                    // do nothing, user has to be more specific
                  } else {
                    // no match, do nothing
                  }
                }
                
                $event.target.value = text;
                $wire.set("srcAndCount", text);
                console.log("New: " + text);
            '
          ])
          ->columnSpan(3),
        Forms\Components\TextInput::make('signatureCount')
          ->label(__('sheet.fields.signatureCountShort'))
          ->required()
          ->validationMessages([
            'required' => __('pages.sheetWorkflow.validation.signatureCount.required'),
          ])
          ->disabled(true),
        Forms\Components\Select::make('source_id')
          ->label(__('source.name'))
          ->required()
          ->validationMessages([
            'required' => __('pages.sheetWorkflow.validation.source_id.empty', ['srcAndCount' => $this->srcAndCount]),
          ])
          ->options(Source::all()->pluck('code', 'id'))
          ->disabled(true),
      ])
      ->columns(2)
      ->columnSpan(2),
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
            $communeNames = $zipcodes->map(function ($zipcode) {
                return $zipcode->commune->nameWithCanton();
            })->unique();
            $places = $zipcodes->map(function ($zipcode) {
                return $zipcode->nameWithCanton();
            })->unique();
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

            $livewire->resetErrorBag($component->getStatePath());
            $livewire->resetErrorBag('place');
            $livewire->resetErrorBag('commune_id');

            $zipcodes = Zipcode::where('code', $livewire->zipcode)->get();
            $communeNamesForZipCode = $zipcodes->map(function ($zipcode) {
                return $zipcode->commune->nameWithCanton();
            })->unique();
            $placeNamesForZipCode = $zipcodes->map(function ($zipcode) {
                return $zipcode->nameWithCanton();
            })->unique();
            $placeAndCommuneNamesForZipCode = $placeNamesForZipCode->merge($communeNamesForZipCode)->unique();

            $filteredPlaces = $placeAndCommuneNamesForZipCode->filter(function ($candidateName) use ($state) {
              // Lowercase both arguments for case-insensitive comparison
              return stripos(mb_strtolower($candidateName), mb_strtolower($state)) === 0;
            });

            // supposed to check if prefix is unique
            // copilot, didn't check
            if ($filteredPlaces->count() > 1) {
              $placesArray = $filteredPlaces->values()->all();
              $first = array_shift($placesArray);
              $prefix = $first;
              foreach ($placesArray as $place) {
                $i = 0;
                $max = min(mb_strlen($prefix), mb_strlen($place));
                while ($i < $max && mb_substr($prefix, $i, 1) === mb_substr($place, $i, 1)) {
                  $i++;
                }
                $prefix = mb_substr($prefix, 0, $i);
                if ($prefix === '') {
                  break;
                }
              }
              $livewire->place = $prefix;
            }
            $place_name = $livewire->place;

            $exactMatch = $placeAndCommuneNamesForZipCode->filter(function ($candidateName) use ($place_name) {
              return mb_strtolower($candidateName) === mb_strtolower($place_name);
            });

            if ($filteredPlaces->count() === 0) {
              $livewire->addError($component->getStatePath(), __('pages.sheetWorkflow.validation.place.not_found', ['zipcode' => $livewire->zipcode, 'place' => $state]));
              $livewire->commune_id = null;
              $livewire->communeHelperText = '';
              return;
            } else if ($filteredPlaces->count() === 1 || $exactMatch->count() === 1) {
              if ($exactMatch->count() === 1) {
                $place_name = $exactMatch->first();
              } else {
                $place_name = $filteredPlaces->first();
              }

              $component->state($place_name);

              // Split place_name to extract name and canton
              $parts = explode(' ', trim($place_name));
              if (count($parts) < 2) {
                throw new \Exception("Programming or places data error: place name does not contain a canton code: " . $place_name);
              }
              
              $canton_code = array_pop($parts);
              $name_part = implode(' ', $parts);
              
              $commune_ids = Zipcode::where('code', $livewire->zipcode)
                ->where(function ($query) use ($name_part) {
                  $query->where('name', $name_part)
                    ->orWhereHas('commune', function ($query) use ($name_part) {
                      $query->where('name', $name_part);
                    });
                })
                ->get()
                ->pluck('commune_id')
                ->unique();

              $communes = Commune::whereIn('id', $commune_ids)
                ->whereHas('canton', function ($query) use ($canton_code) {
                  $query->where('label', $canton_code);
                })
                ->get();

              if ($communes->isEmpty()) {
                // this is not supposed to be possible, make user aware of bug
                throw new \Exception("Programming error, commune not found");
              }
              if ($communes->count() === 1) {
                $livewire->commune_id = $commune_ids->first();
                $livewire->resetErrorBag('commune_id');
                $livewire->placeHelperText = '';
                $commune_name = $communes->first()->nameWithCanton();
                $livewire->communeHelperText = __('pages.sheetWorkflow.validation.commune.determined_zip_place', [
                  'zipcode' => $livewire->zipcode,
                  'place' => $livewire->place,
                  'commune' => $commune_name
                ]);
              } else {
                $commune_names = $communes->map(function ($commune) {
                    return $commune->nameWithCanton();
                })->unique();
                if ($commune_names->contains($place_name)) {
                  $commune = $communes->firstWhere('name', $name_part);
                  $livewire->commune_id = $commune->id;
                }
                $livewire->addError('commune_id', __('pages.sheetWorkflow.validation.commune_id.multiple', [
                  'zipcode' => $livewire->zipcode,
                  'place' => $place_name,
                  'communes' => $commune_names->implode(', ')
                ]));
                $livewire->communeHelperText = '';
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
          ->options(Commune::all()->mapWithKeys(function ($commune) {
              return [$commune->id => $commune->nameWithCanton()];
          }))
          ->disabled(true)
          ->columnSpan(3),
      ])
      ->columns(3)
      ->columnSpan(2),
    ])
    ->columns(5);
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