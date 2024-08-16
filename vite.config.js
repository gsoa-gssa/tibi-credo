import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.js',
                "resources/js/stats/signatureCount.js",
            ],
            refresh: true,
        }),
    ],
    server: {
        hmr: {
            protocol: 'wss',
            host: 'tibi-credo.ddev.site',
        }
    },
    resolve: {
        alias: {
            '@fonts': '/resources/css/typography/fonts',
        },
    },
    esbuild: {
        drop: ["console", "debugger"]
    }
});
