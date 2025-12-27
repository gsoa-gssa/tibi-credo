@php
    $panel = filament()->getCurrentPanel();
@endphp

<x-filament-panels::page.simple>
    @if (filament()->hasLogin())
        <x-slot name="heading">
            {{ __('code_login.heading') }}
        </x-slot>
    @endif

    @if (filament()->hasLogin())
        <x-slot name="subheading">
            {{ __('code_login.explanation') }}
        </x-slot>
    @endif

    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            {{ __('code_login.login_button') }}
        </x-filament::button>
    </form>

    <div class="mt-4 text-center">
        <x-filament::link :href="filament()->getLoginUrl()" size="sm">
            {{ __('code_login.use_password') }}
        </x-filament::link>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page.simple>
