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

    <script>
        const value = "{{$this->label}}";
        if (value != "") {
            document.addEventListener('DOMContentLoaded', function() {
                let input = document.querySelectorAll('.fi-otp-input-container input[type="number"]');
                for(let i = 0; i < input.length; i++) {
                    input[i].value = value.charAt(i);
                };

                document.querySelector("button#vox").addEventListener('click', function() {
                    setTimeout(() => {
                        let input = document.querySelectorAll('.fi-otp-input-container input[type="number"]');
                        if (input.length == 0) {
                            return;
                        }
                        for(let i = 0; i < input.length; i++) {
                            input[i].value = value.charAt(i);
                        };
                    }, 500);
                });
            });
        };
    </script>
</x-filament-panels::page>
