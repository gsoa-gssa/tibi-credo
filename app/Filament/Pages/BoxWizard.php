<?php

namespace App\Filament\Pages;

use App\Models\Box;
use App\Models\Canton;
use App\Models\Maeppli;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Collection;
use App\Filament\Resources\BoxResource;

class BoxWizard extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('pages.BoxWorkflow.navigationLabel');
    }
    
    public function getTitle(): string
    {
        return __('pages.BoxWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.BoxWorkflow.navigationGroup');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    protected static string $view = 'filament.pages.box-wizard';

    public ?int $canton_id = null;
    public $box_choice = null; // int box id or 'new'
    public array $maeppli_ids = [];

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Step::make(__('pages.BoxWorkflow.steps.selectCanton'))
                    ->schema([
                        Select::make('canton_id')
                            ->label(__('canton.name'))
                            ->searchable()
                            ->required()
                            ->options(function () {
                                // Only cantons that have Maeppli without a box
                                $cantonIds = Maeppli::query()
                                    ->whereNull('box_id')
                                    ->join('communes', 'maepplis.commune_id', '=', 'communes.id')
                                    ->whereNotNull('communes.canton_id')
                                    ->pluck('communes.canton_id')
                                    ->unique()
                                    ->values();

                                return Canton::query()
                                    ->whereIn('id', $cantonIds)
                                    ->orderBy('label')
                                    ->pluck('label', 'id');
                            }),
                        Placeholder::make('unavailable_cantons')
                            ->label(__('pages.BoxWorkflow.unavailableCantonsLabel'))
                            ->content(function () {
                                $availableIds = Maeppli::query()
                                    ->whereNull('box_id')
                                    ->join('communes', 'maepplis.commune_id', '=', 'communes.id')
                                    ->whereNotNull('communes.canton_id')
                                    ->pluck('communes.canton_id')
                                    ->unique()
                                    ->values();

                                $unavailable = Canton::query()
                                    ->whereNotIn('id', $availableIds)
                                    ->orderBy('label')
                                    ->pluck('label')
                                    ->toArray();

                                if (empty($unavailable)) {
                                    return __('pages.BoxWorkflow.allCantonsHaveUnassignedMaeppli');
                                }

                                return __('pages.BoxWorkflow.cantonsWithoutUnassignedMaeppli') . implode(', ', $unavailable);
                            }),
                    ]),
                Step::make(__('pages.BoxWorkflow.steps.chooseBox'))
                    ->schema([
                        Radio::make('box_choice')
                            ->label(__('pages.BoxWorkflow.steps.chooseBox'))
                            ->options(function (callable $get) {
                                $cantonId = $get('canton_id');
                                if (!$cantonId) return ['new' => __('pages.BoxWorkflow.newBox')];
                                $cantonLabel = Canton::find($cantonId)?->label;
                                if (!$cantonLabel) return ['new' => __('pages.BoxWorkflow.newBox')];
                                $existing = Box::query()
                                    ->whereHas('maepplis.commune.canton', function ($q) use ($cantonLabel) {
                                        $q->where('label', $cantonLabel);
                                    })
                                    ->orderBy('id')
                                    ->get()
                                    ->mapWithKeys(fn(Box $b) => [$b->id => $b->label])
                                    ->toArray();
                                return ['new' => __('pages.BoxWorkflow.newBox')] + $existing;
                            })
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                if (is_numeric($state)) {
                                    $preselected = Maeppli::where('box_id', (int) $state)->pluck('id')->toArray();
                                    $set('maeppli_ids', $preselected);
                                } else {
                                    $set('maeppli_ids', []);
                                }
                            })
                            ->required()
                            ->inline(false),
                    ]),
                Step::make(__('pages.BoxWorkflow.steps.selectMaepplis'))
                    ->schema([
                        Fieldset::make(__('pages.BoxWorkflow.steps.Maepplis'))
                            ->schema([
                                CheckboxList::make('maeppli_ids')
                                    ->label('')
                                    ->options(function (callable $get) {
                                        $cantonId = $get('canton_id');
                                        if (!$cantonId) return [];
                                        $cantonLabel = Canton::find($cantonId)?->label;
                                        if (!$cantonLabel) return [];

                                        $choice = $get('box_choice');
                                        $existingBoxId = is_numeric($choice) ? (int) $choice : null;

                                        $query = self::maeppliQueryForCanton($cantonLabel, $existingBoxId);

                                        // Build options labels
                                        $options = [];
                                        foreach ($query->get() as $m) {
                                            $label = $m->getDisplayLabelAttribute() . ' (' . ($m->commune?->nameWithCanton() ?? 'n/a') . ')';
                                            $options[$m->id] = $label;
                                        }

                                        return $options;
                                    })
                                    ->columns(1),
                            ]),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('save')
                                ->label(__('pages.BoxWorkflow.save'))
                                ->action('submit')
                                ->submit('submit'),
                        ])->alignEnd(),
                    ]),
            ]),
        ]);
    }

    public function submit(): void
    {
        // Determine box: existing or create new
        $box = null;
        $choice = $this->box_choice;
        if (is_numeric($choice)) {
            $box = Box::find((int) $choice);
        } else {
            $box = Box::create();
        }

        // Assign selected Maepplis to box; canton consistency is enforced in Maeppli::saving
        $ids = collect($this->maeppli_ids)->filter()->all();
        if ($ids) {
            Maeppli::whereIn('id', $ids)->update(['box_id' => $box->id]);
        }

        // set unchecked Maepplis to null box_id
        $maepplieChoices = self::maeppliQueryForCanton(
            Canton::find($this->canton_id)?->label ?? '',
            is_numeric($choice) ? (int) $choice : null
        )->pluck('id')->toArray();
        $unchecked = array_diff($maepplieChoices, $ids);
        if ($unchecked) {
            Maeppli::whereIn('id', $unchecked)->update(['box_id' => null]);
        }
        

        // Redirect to Box edit page
        $this->redirect(BoxResource::getUrl('edit', ['record' => $box]));
    }

    private static function maeppliQueryForCanton(string $cantonLabel, ?int $existingBoxId = null)
    {
        if ($existingBoxId === null) {
            $query = Maeppli::query()
                ->with(['commune.canton'])
                ->whereHas('commune.canton', function ($q) use ($cantonLabel) {
                    $q->where('label', $cantonLabel);
                })
                ->whereNull('box_id');
        } else {
            $query = Maeppli::query()
              ->with(['commune.canton'])
              ->whereHas('commune.canton', function ($q) use ($cantonLabel) {
                  $q->where('label', $cantonLabel);
              })
              ->where(function ($q) use ($existingBoxId) {
                  $q->whereNull('box_id')
                      ->orWhere('box_id', $existingBoxId);
              });
        }

        return $query;
    }
}
