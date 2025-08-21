<x-filament-panels::page>

    <x-filament-panels::form wire:submit="store">
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>

    <div >
        <h3
            class="fi-header-heading text-1xl font-semibold tracking-tight text-gray-950 dark:text-white sm:text-2xl"
        >
            {{__("pages.sheetWorkflow.moreInformation.title")}}
        </h3>
        <p
            class="mt-2 text-gray-600 dark:text-gray-400"
        >
            {{__("pages.sheetWorkflow.moreInformation.description")}}
        </p>
        <div class="mt-6">
            {{$this->table}}
        </div>
    </div>
</x-filament-panels::page>
