<div>
    @if($error)
        <div class="p-8 text-center text-red-500">{{ $error }}</div>
    @else
        <div>
            <h2 class="text-lg font-bold mb-4 filament-heading" style="margin-top:2rem;">{{ __('widgets.source_chart_single.heading_detailed', ['code' => $source->code, 'name' => $source->signatureCollection->short_name]) }}</h2>
            <div class="w-full overflow-x-auto">
                <canvas id="sourceChartSingle" class="max-w-full"></canvas>
            </div>
        </div>
        @vite(['resources/js/app.js'])
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('sourceChartSingle').getContext('2d');
                new window.Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: @json(__('widgets.source_chart_single.chart_label', ['name' => $source->getLocalized('short_description')])),
                            data: @json($data),
                            fill: true,
                            backgroundColor: 'rgba(59,130,246,0.2)',
                            borderColor: 'rgba(59,130,246,1)',
                            tension: 0.3,
                            pointRadius: 0.5,
                        }],
                    },
                    options: {
                        plugins: { legend: { display: true } },
                        scales: { x: { display: true }, y: { display: true } },
                    },
                });
            });
        </script>
    @endif
</div>
