@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading">
        <x-slot name="headerEnd">
            <div class="flex flex-col gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ __('widgets.progress_chart.choose_extrapolation_dates') }}</span>
                <div class="flex items-center gap-2">
                <div class="w-36">
                    <input
                        type="date"
                        wire:model.live="startDate"
                        min="{{ $this->minDate }}"
                        max="{{ $this->maxDate }}"
                        class="w-full rounded-md border-gray-200 bg-white text-gray-700 shadow-sm focus:ring-1 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700"
                    />
                </div>
                <div class="w-36">
                    <input
                        type="date"
                        wire:model.live="endDate"
                        min="{{ $this->minDate }}"
                        max="{{ $this->maxDate }}"
                        class="w-full rounded-md border-gray-200 bg-white text-gray-700 shadow-sm focus:ring-1 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700"
                    />
                </div>
                </div>
            </div>
        </x-slot>

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [50, 400],
                            alias: 'widgets::chart-widget.background',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 500],
                            alias: 'widgets::chart-widget.border',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
