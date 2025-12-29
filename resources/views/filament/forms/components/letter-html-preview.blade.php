<div>
    @if($getRecord() && $getRecord()->letter_html)
        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800">
            <div class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('batch.fields.letter_preview') }}
            </div>
            <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white">
                <iframe 
                    srcdoc="{!! htmlspecialchars($getRecord()->letter_html, ENT_QUOTES) !!}"
                    class="w-full"
                    style="height: 400px; border: none;"
                    sandbox="allow-same-origin"
                ></iframe>
            </div>
        </div>
    @endif
</div>
