<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Pages\Page;

class RegisterInvalid extends Page
{
  protected static ?string $navigationIcon = 'heroicon-o-user-plus';
  protected static ?int $navigationSort = 4;

  public static function getNavigationLabel(): string
  {
      return __('pages.registerInvalid.navigationLabel');
  }

  public static function getNavigationGroup(): ?string
  {
      return __('pages.registerInvalid.navigationGroup');
  }

  public function mount()
  {
    return redirect()->to(\App\Filament\Resources\ContactResource::getUrl('create'));
  }
}
