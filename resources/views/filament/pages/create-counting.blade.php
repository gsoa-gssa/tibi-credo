<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}
        
        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                {{ __('filament-panels::resources/pages/create-record.form.actions.create.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
