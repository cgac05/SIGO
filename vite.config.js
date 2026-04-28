import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        hmr: process.env.VITE_HMR_HOST ? {
            host: process.env.VITE_HMR_HOST,
            protocol: 'ws',
            port: 5173,
        } : false,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/apoyos-app.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: true,
        minify: 'terser',
    },
});
