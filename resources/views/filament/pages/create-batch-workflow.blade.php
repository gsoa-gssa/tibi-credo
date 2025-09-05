<x-filament-panels::page>
    <x-filament-panels::form wire:submit="createBatch">
        {{ $this->createBatchWizard }}
    </x-filament-panels::form>
    <style>
        div[wire\:key*="data.sheets"]:focus-within {
            border:2px solid rgb(71, 71, 71) !important;
            border-radius: 3px;
            transition: background 0.2s;
        }
    </style>
</x-filament-panels::page>
