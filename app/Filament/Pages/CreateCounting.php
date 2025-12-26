<?php

namespace App\Filament\Pages;

use App\Models\Counting;
use App\Filament\Resources\CountingResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class CreateCounting extends Page implements HasForms
{
    use InteractsWithForms;

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
            ->statePath('data')
            ->model(Counting::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            $counting = Counting::create($data);

            Notification::make()
                ->success()
                ->title(__('notification.success'))
                ->send();

            $this->redirect(route('filament.app.resources.countings.view', ['record' => $counting]));
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('notification.error'))
                ->body($e->getMessage())
                ->send();
        }
    }
}
