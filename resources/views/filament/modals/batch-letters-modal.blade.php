@php
    /** @var \Illuminate\Database\Eloquent\Collection|null $records */
    $allHtmlGenerated = $records && $records->every(fn($record) => !empty($record->letter_html));
    $someHtmlGenerated = $records && $records->some(fn($record) => !empty($record->letter_html));
@endphp

<div class="space-y-4">
    @if (! $records || $records->isEmpty())
        <p>{{ __('batch.notification.no_records_selected') }}</p>
    @elseif ($allHtmlGenerated)
        <x-filament::modal.heading>{{ __('batch.action.generate_letters.all_letters_already_generated_heading') }}</x-filament::modal.heading>
        <p>{{ __('batch.action.generate_letters.all_letters_already_generated') }}</p>
        <x-filament::button tag="a" :href="route('batches.html', ['ids' => $records->pluck('id')->join(',')])" target="_blank" size="md" class="w-full">
            {{ __('batch.action.view_generated_letters') }}
        </x-filament::button>
    @else
        @php
            $ids = $records->pluck('id')->join(',');
            $variants = [
                ['label' => __('batch.action.exportLetterLeftA4Priority'), 'addressPosition' => 'left', 'priority' => 'A'],
                ['label' => __('batch.action.exportLetterLeftA4'), 'addressPosition' => 'left', 'priority' => 'B1'],
                ['label' => __('batch.action.exportLetterLeftA4MassDelivery'), 'addressPosition' => 'left', 'priority' => 'B2'],
                ['label' => __('batch.action.exportLetterRightA4Priority'), 'addressPosition' => 'right', 'priority' => 'A'],
                ['label' => __('batch.action.exportLetterRightA4'), 'addressPosition' => 'right', 'priority' => 'B1'],
                ['label' => __('batch.action.exportLetterRightA4MassDelivery'), 'addressPosition' => 'right', 'priority' => 'B2'],
            ];
        @endphp

        <style>
            .batch-letters-columns {
                column-count: 2;
                column-gap: 1rem;
                column-fill: balance;
            }
            .batch-letters-columns > * {
                display: inline-block;
                width: 100%;
                margin: 0 0 0.5rem;
                break-inside: avoid;
            }
            @media (max-width: 640px) {
                .batch-letters-columns { column-count: 1; }
            }
        </style>
        <x-filament::modal.heading>{{ __('batch.action.generate_letters.choose_letter_variant_heading') }}</x-filament::modal.heading>
        <p>{{ __('batch.action.generate_letters.choose_letter_variant_body') }}</p>
        <div class="batch-letters-columns">
            @foreach ($variants as $variant)
                @php
                    $url = route('batches.html', [
                        'ids' => $ids,
                        'addressPosition' => $variant['addressPosition'],
                        'priority' => $variant['priority'],
                    ]);
                @endphp

                <x-filament::button tag="a" :href="$url" target="_blank" size="md" class="w-full">
                    {{ $variant['label'] }}
                </x-filament::button>
            @endforeach
        </div>
        @if ($someHtmlGenerated)
            <p>
                {{ __('batch.action.generate_letters.some_letters_already_generated') }}
            </p>
        @endif
    @endif
</div>
