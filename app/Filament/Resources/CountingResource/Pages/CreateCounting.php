<?php

namespace App\Filament\Resources\CountingResource\Pages;

use App\Filament\Resources\CountingResource;

use App\Models\Counting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Source;
use Filament\Forms\Components\Group;

class CreateCounting extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = CountingResource::class;
    protected static string $view = 'filament.resources.counting-resource.pages.create-counting';

    public ?array $data = [];
    public $srcAndCount = '';
    public $source_id;
    public $count = 0;
    public $date;
    public $name = '';
    public $confirm_large_count = false;
    public $signature_collection_id;

    public function getTitle(): string
    {
        return __('pages.createCounting.name');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $sources = Source::all()->pluck('code', 'id')->toArray();
        return $form->schema([
            Forms\Components\Hidden::make('signature_collection_id')
                ->default(fn () => auth()->user()?->signature_collection_id)
                ->required(),
            Forms\Components\TextInput::make('srcAndCount')
                ->label(__('counting.fields.countAndSourceShortcut'))
                ->helperText(__('counting.helper.countAndSourceShortcut'))
                ->dehydrated(false)
                ->columnSpan('full')
                ->extraAttributes([
                    'x-data' => '{ 
                        sources: ' . \Illuminate\Support\Js::from($sources) . ',
                        multipleMatches: ' . \Illuminate\Support\Js::from(__('counting.validation.multipleMatches')) . ',
                        noMatches: ' . \Illuminate\Support\Js::from(__('counting.validation.noMatches')) . ',
                        reusingSource: ' . \Illuminate\Support\Js::from(__('counting.validation.reusingSource')) . ',
                        noSourceSet: ' . \Illuminate\Support\Js::from(__('counting.validation.noSourceSet')) . ',
                        wrongFormat: ' . \Illuminate\Support\Js::from(__('counting.validation.wrongFormat')) . ',
                        errorMsg: ""
                    }',
                    'x-on:input' => '
                        let text = $event.target.value;
                        text = text.replace(/\s+/g, "");
                        text = text.toUpperCase();
                        errorMsg = "";
                        let modifiedCount = false;

                        if (text) {
                            let match = text.match(/^(\d+)([a-zA-Z]*.*)$/);
                            if (match) {
                                let count = parseInt(match[1]);
                                $wire.count = count;
                                modifiedCount = true;
                                
                                source_part = match[2];
                                // if source_part not empty
                                if (source_part) {
                                    sourcesCandidates = Object.entries(sources).filter(([id, code]) => code.startsWith(source_part));
                                    
                                    if (sourcesCandidates.length === 1) {
                                        $wire.source_id = sourcesCandidates[0][0];
                                    } else if (sourcesCandidates.length > 1) {
                                        let codes = sourcesCandidates.map(([id, code]) => code).join(", ");
                                        $wire.source_id = sourcesCandidates[0][0];
                                        errorMsg = multipleMatches + " " + codes;
                                    } else {
                                        errorMsg = noMatches + " " + source_part;
                                    }
                                } else {
                                    // if there is already a source_id set, keep it and notify user about that through errorMsg
                                    // if there is noe source_id set, tell user in error message to add one
                                    if ($wire.source_id) {
                                        errorMsg = reusingSource;
                                    } else {
                                        errorMsg = noSourceSet;
                                    }
                                }
                            } else {
                                errorMsg = wrongFormat;
                            }
                        }
                        
                        // Show/hide error
                        let errorEl = $el.parentElement.parentElement.querySelector(".srcAndCount-error");
                        if (!errorEl) {
                            errorEl = document.createElement("p");
                            errorEl.className = "srcAndCount-error text-sm text-danger-600 dark:text-danger-400 mt-1";
                            $el.parentElement.parentElement.appendChild(errorEl);
                        }
                        errorEl.textContent = errorMsg;
                        errorEl.style.display = errorMsg ? "block" : "none";
                        
                        $wire.srcAndCount = text;
                    '
                ]),
            Forms\Components\TextInput::make('count')
                ->label(__('counting.fields.count'))
                ->numeric()
                ->required()
                ->extraAttributes([
                    'x-on:input' => '
                        $wire.srcAndCount = null;
                    '
                ]),
            Forms\Components\Select::make('source_id')
                ->label(__('source.name'))
                ->required()
                ->validationMessages([
                    'required' => __('pages.sheetWorkflow.validation.source_id.empty', ['srcAndCount' => $this->srcAndCount]),
                ])
                ->options(Source::all()->pluck('code', 'id'))
                ->extraAttributes([
                    'x-on:input' => '
                        $wire.srcAndCount = null;
                    '
                ]),
            Group::make([
                Forms\Components\Checkbox::make('confirm_large_count')
                    ->label(__('counting.fields.confirmLargeCount'))
                    ->default(false)
                    ->required(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) > 100)
                    ->dehydrated(false)
                    ->extraInputAttributes([
                        'x-bind:required' => '$wire.get("count") > 100',
                    ]),
                ])
                ->columnSpan(2)
                ->extraAttributes([
                            'x-show' => '$wire.get("count") > 100',
                            'x-cloak' => true,
                ]),
            Forms\Components\DatePicker::make('date')
                ->label(__('counting.fields.date'))
                ->default(fn () => now())
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label(__('counting.fields.name'))
                ->maxLength(255)
                ->required(fn (\Filament\Forms\Get $get) => ((int) ($get('count') ?? 0)) > 10)
                ->extraAttributes(['x-ref' => 'nameInput'])
                ->extraInputAttributes([
                    'x-bind:required' => '$wire.get("count") > 10',
                ]),
        ])
        ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Counting::query()->latest('created_at')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('counting.fields.created_at')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('counting.fields.name')),
                Tables\Columns\TextColumn::make('count')
                    ->label(__('counting.fields.count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('source.code')
                    ->label(__('source.name')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            Counting::create($data);

            Notification::make()
                ->success()
                ->title(__('pages.createCounting.save.success'))
                ->send();

            $this->form->fill();
            $this->dispatch('table-reloaded');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('pages.createCounting.save.error'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
