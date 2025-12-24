<x-filament-panels::page>
    <div class="relative">
        <div wire:loading wire:target="performLookup" class="pointer-events-none absolute inset-0 z-10 flex items-start justify-center pt-6 backdrop-blur-sm bg-white/30 dark:bg-gray-900/20">
            <div class="flex items-center gap-2 text-sm text-primary-700 dark:text-primary-300">
                <x-filament::loading-indicator class="h-4 w-4" />
                <span>{{ __('Loading lookup...') }}</span>
            </div>
        </div>

        <div wire:loading.class="opacity-60 blur-[1px]" wire:target="performLookup">
            @if($helperText || !empty($possibleCommuneNamesWithCanton))
                <div class="mb-4 p-4 bg-primary-50 dark:bg-primary-900 rounded-lg">
                    @if(!empty($possibleCommuneNamesWithCanton))
                        <p class="text-sm text-primary-600 dark:text-primary-400">
                          {{ implode(', ', $possibleCommuneNamesWithCanton) }} ({{ $amountOfPossibleCommunes }} {{ __('total') }})
                        </p>
                    @endif
                    @if($helperText)
                        <p class="mt-2 text-sm text-primary-600 dark:text-primary-400">
                            {{ $helperText }}
                        </p>
                    @endif
                </div>
            @endif

            <form wire:submit.prevent="performLookup">
                {{ $this->form }}
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
      function getRows() {
        return Array.from(document.querySelectorAll('[data-address-row]'))
          .sort((a, b) => Number(a.dataset.addressRow) - Number(b.dataset.addressRow));
      }

      function rowHasValue(rowEl) {
        const controls = rowEl.querySelectorAll('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])');
        for (const el of controls) {
          if (el.tagName === 'SELECT') {
            if (el.value && String(el.value).trim() !== '') return true;
            continue;
          }
          const type = (el.type || '').toLowerCase();
          if (type === 'checkbox' || type === 'radio') {
            if (el.checked) return true;
            continue;
          }
          const v = (el.value ?? '').trim();
          if (v !== '') return true;
        }
        return false;
      }

      function recalcVisibility() {
        const rows = getRows();
        if (!rows.length) return;

        // Row 0 always visible
        rows[0].classList.remove('hidden');
        rows[0].style.display = '';

        // Only reveal row i if row i-1 has any value
        for (let i = 1; i < rows.length; i++) {
          const prevHasValue = rowHasValue(rows[i - 1]);
          const hide = !prevHasValue;
          rows[i].classList.toggle('hidden', hide);
          rows[i].style.display = hide ? 'none' : '';
        }
      }

      function attach() {
        const run = () => requestAnimationFrame(recalcVisibility);

        document.addEventListener('input', (e) => {
          if (e.target && e.target.closest('[data-address-row]')) run();
        });
        document.addEventListener('change', (e) => {
          if (e.target && e.target.closest('[data-address-row]')) run();
        });

        document.addEventListener('DOMContentLoaded', run);

        if (window.Livewire && typeof Livewire.hook === 'function') {
          Livewire.hook('message.processed', run);
        }
        window.addEventListener('livewire:load', run);
        window.addEventListener('livewire:navigated', run);

        const observer = new MutationObserver((muts) => {
          for (const m of muts) {
            if (m.type === 'childList' || m.type === 'attributes') { run(); break; }
          }
        });
        observer.observe(document.body, { subtree: true, childList: true, attributes: true });
      }

      attach();
    })();
    </script>
    @endpush
</x-filament-panels::page>
