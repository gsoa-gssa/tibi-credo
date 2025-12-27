<div>
    @if(auth()->user()?->hasRole('super_admin'))
        {{ $this->form }}

        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('reloadPage', () => {
                    window.location.reload();
                });
            });
        </script>
    @endif
</div>
