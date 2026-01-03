@php
    /** @var \Illuminate\Database\Eloquent\Collection|null $records */
@endphp

<div class="space-y-4">
    @if (! $records || $records->isEmpty())
        <p>{{ __('batch.notification.no_records_selected') }}</p>
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
    @endif
</div>
