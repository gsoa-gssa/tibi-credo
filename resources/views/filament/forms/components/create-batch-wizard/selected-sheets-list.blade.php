<div class="mt-4">
    @php
        $sheets = $this->sheetsLabels();
    @endphp
    @if($sheets->isNotEmpty())
        <div>
            <h4 class="font-medium mb-2">{{ __('pages.createBatchWorkflowB.review.selectedSheets') }}</h4>
            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">
                {{ $sheets->implode(', ') }}
            </div>
        </div>
    @else
        <div class="text-sm text-orange-600">
            {{ __('pages.createBatchWorkflowB.review.noSheetsSelected') }}
        </div>
    @endif
</div>
