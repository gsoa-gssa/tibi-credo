<p class="font-bold">
    @php
        $sheets = $this->sheetsLabels();
    @endphp
    @if($sheets->isNotEmpty())
        <div class="mt-4">
            <h4 class="font-medium mb-2">{{ __('pages.captureBatchWorkflow.selectedSheetsList.title') }}</h4>
            <div class="text-sm text-gray-600">
                {{ $sheets->implode(', ') }}
            </div>
        </div>
    @endif
</p>