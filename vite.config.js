import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                css: 'resources/css/app.css',
                toggleTooltips: 'resources/js/component-addons/toggle-tooltips.js',
                toggleRequired: 'resources/js/forms/toggle-required.js',
                displayUploadedFile: 'resources/js/forms/display-uploaded-file.js',
                displayUploadedFileName: 'resources/js/forms/display-uploaded-file-name.js',
                editorjs: 'resources/js/editor/editorjs.js',
            },
            output: {
                assetFileNames: '[name][extname]',
                entryFileNames: '[name].js',
            },
        },
    },
});
