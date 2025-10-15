<?php

namespace App\Filament\Pages;

use App\Models\Contact;
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
      ->schema([
        Forms\Components\TextInput::make('firstname')
          ->label(__('contact.fields.firstname'))
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('lastname')
          ->label(__('contact.fields.lastname'))
          ->helperText(__('pages.registerInvalid.lastname_helper'))
          ->maxLength(255),
        Forms\Components\TextInput::make('street_no')
          ->label(__('contact.fields.street_no'))
          ->helperText(__('pages.registerInvalid.street_no_helper'))
          ->maxLength(255),
        Forms\Components\DatePicker::make('birthdate')
          ->label(__('contact.fields.birthdate'))
          ->helperText(__('pages.registerInvalid.birthdate_helper')),
        Forms\Components\TextInput::make('sheet_id')
          ->label(__('sheet.name'))
          ->helperText(__('pages.registerInvalid.sheet_id_helper'))
          ->live()
          ->rules([
              function () {
                  return function (string $attribute, $value, \Closure $fail) {
                      if ($value && !\App\Models\Sheet::where('label', $value)->exists()) {
                          $fail('The selected sheet does not exist.');
                      }
                  };
              },
          ])
          ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
              if ($state) {
                $sheet = \App\Models\Sheet::where('label', $state)->first();
                if($sheet) {
                  $zipcode = $sheet->commune->zipcodes()->first();
                  if ($zipcode) {
                      $set('zipcode_id', $zipcode->id);
                      $set('lang', $zipcode->commune->lang);
                  }
                }
              }
          })
          ->suffixIcon(function (Forms\Get $get) {
              $id = $get('sheet_id');
              if (!$id) return null;

              $sheet = \App\Models\Sheet::find($id);
              return $sheet ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
          })
          ->suffixIconColor(function (Forms\Get $get) {
              $id = $get('sheet_id');
              if (!$id) return null;

              $sheet = \App\Models\Sheet::find($id);
              return $sheet ? 'success' : 'danger';
          }),
        Forms\Components\Select::make('zipcode_id')
          ->label(__('zipcode.name'))
          ->helperText(__('pages.registerInvalid.zipcode_id_helper'))
          ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} {$record->name}")
          ->relationship('zipcode', 'name')
          ->searchable(['code', 'name'])
          ->preload()
          ->live()
          ->afterStateUpdated(function ($state, Forms\Set $set) {
              if ($state) {
                  $zipcode = \App\Models\Zipcode::find($state);
                  if ($zipcode && $zipcode->commune) {
                      $set('lang', $zipcode->commune->lang);
                  }
              }
          }),
        Forms\Components\ToggleButtons::make('lang')
          ->label(__('commune.fields.lang'))
          ->options([
              'de' => 'German',
              'fr' => 'French',
              'it' => 'Italian',
          ])
          ->required()
          ->inline()
          ->afterStateHydrated(function (Forms\Components\ToggleButtons $component, $state, $record) {
              if ($record && $record->lang) {
                  return;
              }

              if ($record && $record->zipcode && $record->zipcode->commune) {
                  $component->state($record->zipcode->commune->lang);
              }
          }),
        Forms\Components\Select::make('contact_type_id')
          ->label(__('contact.fields.contact_type'))
          ->relationship('contactType', 'name')
          ->required()
          ->searchable()
          ->preload(),
      ])
      ->columns(2)
      ->statePath('data')
      ->model(Contact::class);
  }

  public function submit(): void
  {
    $data = $this->form->getState();
    
    try {
      // Process the invalid signature registration
      // Add your logic here, for example:
      // InvalidSignature::create($data);

      Contact::create($data);
      
      Notification::make()
        ->title(__('pages.registerInvalid.notifications.success.title'))
        ->body(__('pages.registerInvalid.notifications.success.body'))
        ->success()
        ->send();
      
      // Reset the form but keep the sheet_id and zipcode_id
      $currentSheetLabel = null;
      if (isset($data['sheet_id'])) {
          $sheet = \App\Models\Sheet::find($data['sheet_id']);
          $currentSheetLabel = $sheet ? $sheet->label : null;
      }
      $currentZipcodeId = $data['zipcode_id'] ?? null;
      $this->form->fill();
      if ($currentSheetLabel) {
          $this->form->fill(['sheet_id' => $currentSheetLabel]);
      }
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
