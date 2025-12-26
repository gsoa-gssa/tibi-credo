<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use App\Models\SignatureCollection;

class SignatureCollectionSelector extends Component implements hasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public array $collections = [];

    public function mount(): void
    {
        $this->collections = SignatureCollection::pluck('short_name', 'id')->toArray();
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.signature-collection-selector');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedSignatureCollectionId')
                    ->hiddenLabel()
                    ->options($this->collections)
                    ->default(auth()->user()?->signature_collection_id ?? array_key_first($this->collections))
                    ->live()
                    ->afterStateUpdated(function (string $state) {
                        $user = auth()->user();
                        if ($user && $user->hasRole('super_admin')) {
                            $user->signature_collection_id = $state;
                            $user->save();
                            $this->dispatch('reloadPage');
                        }
                    }),
            ])
            ->statePath('data');
    }
}
