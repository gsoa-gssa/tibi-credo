<div>
    @if(auth()->user()?->hasAnyRole(['admin', 'super_admin']))
        <x-filament::badge color="danger" size="lg" style="font-size: 1rem; font-weight: 700; padding: 0.625rem 1rem;">
            {{ __('role.admin_warning') }}
        </x-filament::badge>
    @endif
</div>
