import zoomPlugin from 'chartjs-plugin-zoom';

// Register the plugin globally for Chart.js via Filament
window.filamentChartJsPlugins = window.filamentChartJsPlugins || [];
window.filamentChartJsPlugins.push(zoomPlugin);