import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import viteCompression from 'vite-plugin-compression';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        viteCompression({ algorithm: 'gzip', ext: '.gz' }),
        viteCompression({ algorithm: 'brotliCompress', ext: '.br' }),
    ],
    build: {
        cssMinify: true,
        minify: 'esbuild',
        target: 'es2020',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});