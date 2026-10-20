import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // Self-hosted at build time. Only the Latin UI fonts are preloaded; script fonts
            // load on demand through unicode-range when that script appears on the page.
            fonts: [
                google('Figtree', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                    fallbacks: ['system-ui', 'sans-serif'],
                    optimizedFallbacks: false,
                }),
                google('Bricolage Grotesque', {
                    weights: [500, 700, 800],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 700 }],
                    fallbacks: ['system-ui', 'sans-serif'],
                    optimizedFallbacks: false,
                }),
                google('JetBrains Mono', {
                    weights: [500, 700],
                    preload: false,
                    fallbacks: ['ui-monospace', 'monospace'],
                    optimizedFallbacks: false,
                }),
                google('Noto Sans Bengali', {
                    weights: [400, 600, 700],
                    subsets: ['bengali'],
                    preload: false,
                    fallbacks: ['sans-serif'],
                    optimizedFallbacks: false,
                }),
                google('Noto Sans Devanagari', {
                    weights: [400, 600, 700],
                    subsets: ['devanagari'],
                    preload: false,
                    fallbacks: ['sans-serif'],
                    optimizedFallbacks: false,
                }),
                google('Noto Naskh Arabic', {
                    weights: [400, 600, 700],
                    subsets: ['arabic'],
                    preload: false,
                    fallbacks: ['serif'],
                    optimizedFallbacks: false,
                }),
                google('Noto Sans Sinhala', {
                    weights: [400, 600],
                    subsets: ['sinhala'],
                    preload: false,
                    fallbacks: ['sans-serif'],
                    optimizedFallbacks: false,
                }),
                google('Noto Sans Tamil', {
                    weights: [400, 600],
                    subsets: ['tamil'],
                    preload: false,
                    fallbacks: ['sans-serif'],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
});
