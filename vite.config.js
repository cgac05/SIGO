import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

// Load .env variables
const __dirname = dirname(fileURLToPath(import.meta.url));
dotenv.config({ path: `${__dirname}/.env` });

// Get ngrok URL from environment or use localhost
const isNgrok = process.env.NGROK_URL ? true : false;
const ngrokUrl = process.env.NGROK_URL || 'localhost';
const hmrHost = isNgrok ? ngrokUrl.replace(/^https?:\/\//, '') : 'localhost';
const hmrProtocol = isNgrok ? 'wss' : 'ws';

export default defineConfig({
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        cors: {
            origin: [ngrokUrl, 'http://localhost', 'http://localhost:5173'],
            credentials: true
        },
        hmr: {
            host: hmrHost,
            protocol: hmrProtocol,
            port: 5173,
        },
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
});
