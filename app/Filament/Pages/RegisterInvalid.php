<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Filament\Resources\ContactResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class RegisterInvalid extends Page implements HasForms
{
  use InteractsWithForms;

  protected static ?string $navigationIcon = 'heroicon-o-user-plus';
  protected static ?int $navigationSort = 4;
  protected static string $view = 'filament.pages.register-invalid';

  public ?array $data = [];

  protected static string $model = Contact::class;
  //copilot wanted this, maybe unnecessary

  public function getTitle(): string
  {
      return __('pages.registerInvalid.title');
  }

  public static function getNavigationLabel(): string
  {
      return __('pages.registerInvalid.navigationLabel');
  }

  public static function getNavigationGroup(): ?string
  {
      return __('pages.registerInvalid.navigationGroup');
  }

  public function mount(): void
  {
    $this->form->fill();
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema(ContactResource::getFormSchema(true))
      ->columns(2)
      ->statePath('data')
      ->model(Contact::class);
  }

  public function submit(): void
  {
    $data = $this->form->getState();
    
    try {
      $contactData = $data;

      Contact::create($contactData);
      
      Notification::make()
        ->title(__('pages.registerInvalid.notifications.success.title'))
        ->body(__('pages.registerInvalid.notifications.success.body'))
        ->success()
        ->send();
      
      // Reset the form but keep the zipcode_id
      $currentZipcodeId = $data['zipcode_id'] ?? null;
      $this->form->fill();
      if ($currentZipcodeId) {
          $this->form->fill(['zipcode_id' => $currentZipcodeId]);
      }
        
    } catch (\Exception $e) {
      Notification::make()
        ->title(__('pages.registerInvalid.notifications.error.title'))
        ->body(__('pages.registerInvalid.notifications.error.body') . ' ' . $e->getMessage())
        ->danger()
        ->send();
    }
  }
}
