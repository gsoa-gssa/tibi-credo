<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}
        
        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                {{ __('pages.registerInvalid.submit', ['default' => 'Create Contact']) }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
