<?php

namespace App\Filament\Pages;

use App\Models\Address;
use App\Models\Commune;
use App\Models\Zipcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Contracts\Support\Htmlable;

class AddressLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.address-lookup';
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?int $navigationSort = 1;

    public function getTitle(): string | Htmlable
    {
        return __('pages.addressLookup.name');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.addressLookup.name');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.workflows');
    }

    public $zipcode = '';
    public $canton = '';
    public $place = '';
    public $addressRows = [];
    
    public $helperText = '';
    public $placeIgnored = false;
    public $possibleCommuneIDs = [];
    public $possibleCommuneNamesWithCanton = [];
    public $amountOfPossibleCommunes = 0;

    protected $maxRows = 10;

    /**
     * Override addError to concatenate multiple errors for the same field
     * instead of replacing them.
     */
    public function addError($key, $message)
    {
        $errorBag = $this->getErrorBag();
        $currentErrors = $errorBag->get($key);
        
        if (!empty($currentErrors)) {
            // If there are existing errors, merge them with the new one
            if (is_array($currentErrors)) {
                $message = implode(' / ', array_merge($currentErrors, [$message]));
            } else {
                $message = $currentErrors . ' / ' . $message;
            }
            // Remove the old errors before adding the concatenated one
            $this->resetErrorBag(collect([$key]));
        }
        
        parent::addError($key, $message);
    }

    public function mount()
    {
        $this->maxRows = config('address-lookup.max_rows', 10);
        $this->initializeAddressRows();
    }

    protected function initializeAddressRows()
    {
        $this->addressRows = [];
        for ($i = 0; $i < $this->maxRows; $i++) {
            $this->addressRows[$i] = [
                'street_name' => '',
                'street_number' => '',
                'error' => '',
                'ignored' => false,
                ];
        }
    }

    public function form(Form $form): Form
    {
        $components = [];
        $components[] = Forms\Components\Group::make([
          Forms\Components\Placeholder::make('spacer')
            ->label('')
            ->columnSpan(3),
          Forms\Components\Actions::make([
          Forms\Components\Actions\Action::make('clearForm')
            ->label(__('pages.addressLookup.clearForm'))
            ->action(fn () => $this->clearForm())
            // ->color('danger')
            ->icon('heroicon-o-trash')
            ->extraAttributes(['class' => 'w-full']),
          ]),
        ])->columns(4);
        $components[] = Forms\Components\Group::make([
                Forms\Components\TextInput::make('zipcode')
                    ->label(__('zipcode.name'))
                    ->numeric()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function () {
                        $this->performLookup();
                    }),
                Forms\Components\TextInput::make('canton')
                    ->label(__('canton.name'))
                    ->maxLength(2)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function () {
                        $this->performLookup();
                    }),
                Forms\Components\TextInput::make('place')
                    ->label(__('pages.addressLookup.placeLabel'))
                    ->live(onBlur: true)
                    ->afterStateUpdated(function () {
                        $this->performLookup();
                    }),
            ])->columns(3);

        // Add address rows
        for ($i = 0; $i < $this->maxRows; $i++) {
            $components[] = Forms\Components\Group::make([
                Forms\Components\TextInput::make("addressRows.{$i}.street_name")
                    ->label(__("pages.addressLookup.streetN", ['number' => $i + 1]))
                    ->live(onBlur: true)
                    ->afterStateUpdated(function () {
                        $this->performLookup();
                    })
                    ->suffix(fn () => $this->addressRows[$i]['ignored'] ? '⚠️' : '')
                    ->helperText(fn () => $this->addressRows[$i]['ignored'] ? __("pages.addressLookup.rowIgnored") : '')
                    ->extraAttributes(['data-row-field' => 'street_name'])
                    ->columnSpan(2),
                Forms\Components\TextInput::make("addressRows.{$i}.street_number")
                    ->label(__("address.fields.street_number"))
                    ->live(onBlur: true)
                    ->afterStateUpdated(function () {
                        $this->performLookup();
                    })
                    ->suffix(fn () => $this->addressRows[$i]['ignored'] ? '⚠️' : '')
                    ->helperText(fn () => $this->addressRows[$i]['ignored'] ? __("pages.addressLookup.rowIgnored") : '')
                    ->extraAttributes(['data-row-field' => 'street_number']),
            ])
            ->columns(3)
            ->extraAttributes(['data-address-row' => (string) $i]);
        }
        return $form->schema($components);
    }

    public function clearForm()
    {
        $this->zipcode = '';
        $this->canton = '';
        $this->place = '';
        $this->initializeAddressRows();
        $this->helperText = '';
        $this->possibleCommuneIDs = [];
        $this->possibleCommuneNamesWithCanton = [];
        $this->amountOfPossibleCommunes = 0;
        $this->resetErrorBag();
    }

    /**
     * Perform the address lookup based on the current inputs.
     * @return void
     * 
     * Algorithm:
     * 1. Validate zipcode format and canton in client-side js.
     * 1.5: Clean up all inputs by calling $this->cleanInputs().
     * 2. Query communes based on zipcode, canton, and place, ignoring blanks (at least one of the three must be filled).
     * 3. If no address rows filled, call $this->computeMessages() and return commune results.
     * 4. If address rows filled, query addresses within the filtered communes. If results found, call $this->computeMessages() and return results.
     * 5. If no addresses found, attempt to relax filters by:
     *   a) Ignoring any single row. For the first, 2nd, ith row, try ignoring it and re-querying. If results found, mark that row as ignored, call $this->computeMessages() and return results.
     *   b) Ignoring zipcode, canton and place. If results found, set $this->placeIgnored = true, call $this->computeMessages() and return results.
     * 6. If still no results, call $this->computeMessages() and return no results.
     * 
     */
    public function performLookup()
    {
        $this->resetErrorBag();
        $this->ignoredRows = [];
        $this->helperText = '';
        $this->placeIgnored = false;

        // Reset ignored flags
        foreach ($this->addressRows as $key => $row) {
          $this->addressRows[$key]['ignored'] = false;
          $this->addressRows[$key]['error'] = '';
        }

        $this->cleanInputs();

        $communeIDsPlaceRow = $this->getCommuneIDsFromPlaceRow();

        if (!$this->haveAddressRows()) {
          // No address rows filled, return communes based on place row only
          $this->possibleCommuneIDs = $communeIDsPlaceRow;
          $this->computeMessages();
          return;
        }

        if ($this->placeRowEmpty()) {
          $communeIDs = $this->getCommuneIDsFromAddressRows();
        } else {
          $communeIDsAddressRows = $this->getCommuneIDsFromAddressRows($communeIDsPlaceRow);
          $communeIDs = $communeIDsPlaceRow->intersect($communeIDsAddressRows)->values();
        }

        if(!$communeIDs->isEmpty()) {
          // based on place row (if not empty) and address rows we have matches, done
          $this->possibleCommuneIDs = $communeIDs;
          $this->computeMessages();
          return;
        }

        // No matches found, try removing rows one by one if there is more than one
        if (count($this->getFilledAddressRows()) >= 2) {

          for ($i = 0; $i < count($this->addressRows); $i++) {
            if( empty($this->addressRows[$i]['street_name']) && empty($this->addressRows[$i]['street_number'])) {
              continue;
            }
            $this->addressRows[$i]['ignored'] = true;
            $communeIDsAddressRows = $this->getCommuneIDsFromAddressRows($communeIDsPlaceRow);
            $communeIDs = $communeIDsPlaceRow->intersect($communeIDsAddressRows)->values();
            if (!$communeIDs->isEmpty()) {
              $this->possibleCommuneIDs = $communeIDs;
              $this->computeMessages();
              return;
            }
            $this->addressRows[$i]['ignored'] = false;
          }
        }

        // try ignoring place row
        $communeIDs = $this->getCommuneIDsFromAddressRows();
        if (!$communeIDs->isEmpty()) {
          // matches found ignoring place row
          $this->possibleCommuneIDs = $communeIDs;
          $this->placeIgnored = true;
          $this->computeMessages();
          return;
        }

        // no results found
        $this->possibleCommuneIDs = collect([]);
        $this->computeMessages();
    }

    /**
     * Clean up inputs. They are normalized and compared to DB values.
     * If they match, they are replaced with the DB value.
     * Otherwise the original not normalized value is kept.
     */
    protected function cleanInputs()
    {
        // Clean zipcode and if it does not exist set validation error
        $this->zipcode = trim($this->zipcode);
        if (!empty($this->zipcode)) {
          $zipcodeRecord = Zipcode::where('code', $this->zipcode)->first();
          if (!$zipcodeRecord) {
            $this->addError('zipcode', __('pages.addressLookup.zipcodeDoesNotExist'));
          }
        }

        // Clean canton
        $cantonCandidate = strtoupper(trim($this->canton));
        if(!empty($cantonCandidate)){
          if (in_array($cantonCandidate, \App\Models\Canton::labels())) {
              $this->canton = $cantonCandidate;
          } else {
              $this->addError('canton', __('pages.addressLookup.cantonDoesNotExist'));
          }
        }

        // Clean place by comparing it to zipcode names and commune names
        $placeCandidate = strtolower(trim($this->place));
        if (!empty($placeCandidate)) {
          $matchingCommunes = Commune::whereRaw('LOWER(name) = ?', [$placeCandidate])->get();
          // if there is exactly one match, use its name
          if ($matchingCommunes->count() === 1) {
              $this->place = $matchingCommunes->first()->name;
          } else {
            // check zipcode names
            $matchingZipcodes = Zipcode::whereRaw('LOWER(name) = ?', [$placeCandidate])->get();
            if ($matchingZipcodes->count() === 1) {
              $this->place = $matchingZipcodes->first()->name;
            } else {
              // no unique match found, keep original
              $this->place = trim($this->place);
              if($matchingCommunes->isEmpty() && $matchingZipcodes->isEmpty()) {
                $this->addError('place', __('pages.addressLookup.placeDoesNotExist'));
              }
            }
          }
        }

        // Clean address rows
        // TODO
    }

    /**
     * Compute helper message, errors and results based on current state.
     * 
     * @return void
     * 
     * Algorithm:
     * Map first three commune Ids to names with canton.
     * Set helper text.
     */
    protected function computeMessages()
    {
      
      $this->amountOfPossibleCommunes = count($this->possibleCommuneIDs);
      $this->possibleCommuneNamesWithCanton = Commune::whereIn('id', $this->possibleCommuneIDs->take(3))->pluck('name_with_canton_and_zipcode')->toArray();

      if ($this->possibleCommuneIDs->isEmpty()) {
        $this->helperText = __('pages.addressLookup.noMatchingCommunes');
      } elseif (count($this->possibleCommuneIDs) === 1) {
        if ($this->haveIgnoredAddressRows()) {
          $this->helperText = __('pages.addressLookup.oneRowIgnored');
        } elseif ($this->placeIgnored) {
          $this->helperText = __('pages.addressLookup.placeIgnored');
        } else {
          $this->helperText = __('pages.addressLookup.oneMatchingCommune');
        }
      } else {
        $this->helperText = __('pages.addressLookup.multipleCommunes');
      }

      if ($this->haveIgnoredAddressRows()) {
        foreach ($this->addressRows as $key => $row) {
          if ($row['ignored'] === true) {
            $this->addressRows[$key]['error'] = __('pages.addressLookup.rowWasIgnored');
          }
        }
      }

      if ($this->placeIgnored) {
        if (!empty($this->zipcode)) {
          $this->addError('zipcode', __('pages.addressLookup.zipcodeIgnored'));
        }
        if (!empty($this->canton)) {
          $this->addError('canton', __('pages.addressLookup.cantonIgnored'));
        }
        if (!empty($this->place)) {
          $this->addError('place', __('pages.addressLookup.placeIgnoredError'));
        }
      }
    }

    protected function haveAddressRows()
    {
        foreach ($this->addressRows as $row) {
            if (!empty($row['street_name']) || !empty($row['street_number'])) {
                return true;
            }
        }
        return false;
    }

    protected function haveIgnoredAddressRows()
    {
        foreach ($this->addressRows as $row) {
            if (!empty($row['ignored']) && $row['ignored'] === true) {
                return true;
            }
        }
        return false;
    }

    protected function placeRowEmpty()
    {
      return empty($this->zipcode) && empty($this->canton) && empty($this->place);
    }

    /**
     * Get the communes based on zipcode, canton, and place.
     * The inputs are cleaned before, so an exact match is attempted.
     * If all three are blank, return empty collection.
     */
    protected function getCommuneIDsFromPlaceRow()
    {
      if ($this->placeRowEmpty()) {
        return collect([]);
      }
      return Commune::query()
          ->when(!empty($this->zipcode), function ($query) {
              $query->whereHas('zipcodes', function ($q) {
                  $q->where('code', $this->zipcode);
              });
          })
            ->when(!empty($this->canton), function ($query) {
              $query->whereHas('canton', function ($q) {
                $q->where('label', $this->canton);
              });
          })
            ->when(!empty($this->place), function ($query) {
              $query->where(function ($q) {
                $q->where('name', $this->place)
                ->orWhereHas('zipcodes', function ($zipQuery) {
                  $zipQuery->where('name', $this->place);
                });
              });
          })
          ->pluck('id');
    }

    /**
     * Get only the filled address rows which are not ignored.
     */
    protected function getFilledAddressRows()
    {
        $filled = [];
        foreach ($this->addressRows as $index => $row) {
            if (!empty($row['ignored']) && $row['ignored'] === true) {
                continue;
            }
            if (!empty($row['street_name']) || !empty($row['street_number'])) {
                $filled[$index] = $row;
            }
        }
        return $filled;
    }

    /**
     * Get the communes based on address rows, refining a list of possible communes.
     * The inputs are cleaned before, so an exact match is attempted.
     * Rows are ignored if they are marked as such.
     * It's not possible that no rows are filled, that case is handled separately.
     * @param \Illuminate\Support\Collection|null $refine_commune_ids
     * @return \Illuminate\Support\Collection of commune IDs

     */
    protected function getCommuneIDsFromAddressRows($refine_commune_ids = null)
    {
        $rows = $this->getFilledAddressRows();
        if (empty($rows)) {
          throw new \Exception("Internal error: no filled address rows to process.");
        }

        $query = Commune::query();

        // Apply refinement if provided
        // if $refine_commune_ids is not null or a collection, throw error
        if (!is_null($refine_commune_ids) && !($refine_commune_ids instanceof \Illuminate\Support\Collection)) {
          throw new \Exception("Internal error: refine_commune_ids must be null, or a collection.");
        }
        if (!is_null($refine_commune_ids)) {
          $query->whereIn('id', $refine_commune_ids);
        }

        // For each filled row, ensure the commune has a matching address
        foreach ($rows as $row) {
          $query->whereHas('addresses', function ($q) use ($row) {
            if (!empty($row['street_name'])) {
              $q->where('street_name', $row['street_name']);
            }
            if (!empty($row['street_number'])) {
              $q->where('street_number', $row['street_number']);
            }
          });
        }

        return $query->pluck('id');
    }
}