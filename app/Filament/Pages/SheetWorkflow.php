<?php

namespace App\Filament\Pages;

use Filament\Forms;
use App\Models\Sheet;
use App\Models\Zipcode;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Settings\SheetsSettings;
use Illuminate\Support\Facades\File;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Contracts\Support\Htmlable;

class SheetWorkflow extends Page implements HasForms
{
    use InteractsWithForms;
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

    public $numerator_id;
    public $source;
    public $signatureCount;
    public $commune_id;
    public $user_id;
    public $scanUrl;
    public $scanPath;
    public $scanName;

    public function mount()
    {
        $allScans = File::files(storage_path('app/public/sheet-scans/unassigned'));
        if (count($allScans) == 0) {
            Notification::make()
                ->danger()
                ->seconds(15)
                ->title('No Sheets to Process')
                ->send();
            $this->redirect(route('filament.app.pages.dashboard'));
            return;
        }

        $scan = File::files(storage_path('app/public/sheet-scans/unassigned'))[rand(0, count($allScans) - 1)];

        if (!$scan) {
            return;
        }
        $this->scanPath = explode("app/", $scan->getPathname())[1];
        $this->scanUrl = Storage::url($this->scanPath);
        $this->scanName = $scan->getFilename();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('numerator_id')
                ->options(fn() => \App\Models\Numerator::doesntHave('sheet')->get()->pluck('id', 'id')->toArray())
                ->label(__('Numerator ID'))
                ->helperText(__('The 6-digit number on the top right of the signature sheet.'))
                ->searchable()
                ->autofocus()
                ->required(),
            Forms\Components\Select::make("source")
                ->required()
                ->label('Source')
                ->options(
                    function() {
                        return collect(app(SheetsSettings::class)->sources)
                            ->pluck('value', 'label')
                            ->toArray();
                    }
                )
                ->helperText(__('The three letters on the top left of the signature sheet.'))
                ->searchable()
                ->native(false),
            Forms\Components\TextInput::make('signatureCount')
                ->label('Signature Count')
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
                ->required(),
        ])
        ->columns(2);
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
            Action::make('skip')
                ->label(__('Skip'))
                ->keyBindings(['mod+d'])
                ->color("warning")
                ->outlined()
                ->action(fn() => $this->skip())
        ];
    }

    public function store()
    {
        $this->validate();
        $data = $this->form->getState();
        $data['user_id'] = auth()->id();

        $existingSheet = Sheet::where('numerator_id', $data['numerator_id'])->first();
        if ($existingSheet) {
            Notification::make()
                ->danger()
                ->seconds(15)
                ->title(__('Sheet Already Exists, Skipping'))
                ->send();
            redirect(route('filament.app.pages.sheet-workflow'));
            return;
        }

        $sheet = Sheet::create($data);

        Notification::make()
            ->success()
            ->seconds(15)
            ->title('Sheet Created')
            ->send();
        Storage::move($this->scanPath, 'public/sheet-scans/assigned/' . $this->numerator_id . '_' . $this->scanName);
        $this->redirect(route('filament.app.pages.sheet-workflow'));
    }

    public function skip()
    {
        Storage::move($this->scanPath, 'public/sheet-scans/skipped/' . $this->scanName);
        $this->redirect(route('filament.app.pages.sheet-workflow'));
    }

    public static function getNavigationBadge(): ?string
    {
        return count(glob(storage_path('app/public/sheet-scans/unassigned/*.pdf')));
    }

    protected static string $view = 'filament.pages.sheet-workflow';
}
