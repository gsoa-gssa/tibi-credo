@if(auth()->user()?->hasRole('super_admin'))
<div>
  {{ $this->form }}
</div>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('reloadPage', () => {
            window.location.reload();
        });
    });
</script>
@endif
