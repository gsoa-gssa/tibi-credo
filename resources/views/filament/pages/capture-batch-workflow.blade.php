<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <x-filament::section>
            <form wire:submit="create" class="space-y-6">
                {{ $this->form }}

                <x-filament::button type="submit" class="w-full">
                    {{ __('maeppli.button.save') }}
                </x-filament::button>
            </form>
        </x-filament::section>

        <!-- Table Section -->
        @if ($this->table)
            <x-filament::section>
                <h2 class="text-lg font-semibold mb-4">{{ __('pages.captureBatchWorkflow.recentCreated') }}</h2>
                {{ $this->table }}
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
