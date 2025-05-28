<x-filament-panels::page>

    <x-filament-panels::form wire:submit="initiateBatch" >
        {{ $this->initiateBatchForm }}
        @if (!$this->pageTwo)
            <x-filament-panels::form.actions :actions="$this->initiateBatchActions()" />
        @endif
    </x-filament-panels::form>

    @if ($this->pageTwo)
        <x-filament-panels::form wire:submit="addSheetsManuallySubmit" >
            {{ $this->addSheetsManuallyForm }}
            <x-filament-panels::form.actions :actions="$this->addSheetsManuallyActions()" />
        </x-filament-panels::form>
    @endif

    <style>
        div[wire\:key*="sheetsData.sheet_checkbox_"]:focus-within {
            border:2px solid rgb(71, 71, 71) !important;
            border-radius: 3px;
            transition: background 0.2s;
        }
    </style>
</x-filament-panels::page>
