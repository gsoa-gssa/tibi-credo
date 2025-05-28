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
        textarea {
            font-family: monospace !important;
        }
    </style>
</x-filament-panels::page>
