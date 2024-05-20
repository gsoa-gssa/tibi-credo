<x-filament-panels::page>

    <x-filament-panels::form wire:submit="store">
        <iframe
            src="{{$this->scanUrl}}"
            frameborder="0"
            style="
                height: 50vh;
                aspect-ratio: 16 / 9;
                margin: auto;
            "></iframe>
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page>
