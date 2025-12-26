<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}
        
        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                {{ __('filament-panels::resources/pages/create-record.form.actions.create.label') }}
            </x-filament::button>
        </div>
    </form>

    <div class="mt-12">
        <h3 class="text-lg font-semibold mb-4">{{ __('pages.createBatchWorkflow.recentLabel') }}</h3>
        {{ $this->table }}
    </div>
</x-filament-panels::page>