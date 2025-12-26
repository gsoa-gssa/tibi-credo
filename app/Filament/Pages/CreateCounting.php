<?php

namespace App\Filament\Pages;

use App\Models\Counting;
use App\Filament\Resources\CountingResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CreateCounting extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.create-counting';

    public ?array $data = [];

    protected static string $model = Counting::class;

    public function getTitle(): string
    {
        return __('counting.name');
    }

    public static function getNavigationLabel(): string
    {
        return __('counting.name');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group.workflows');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(CountingResource::getFormSchema())
            ->columns(2)
            ->statePath('data')
            ->model(Counting::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Counting::query()->latest('created_at')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('count')
                    ->numeric()
                    ->sortable(),
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

