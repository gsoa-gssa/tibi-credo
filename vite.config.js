import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.js',
                "resources/js/stats/signatureCount.js",
                'resources/js/bwip-datamatrix.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        cors: true,
        headers: { 'Access-Control-Allow-Origin': '*' },
        hmr: {
            protocol: 'wss',
            host: 'certimi.ddev.site',
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
