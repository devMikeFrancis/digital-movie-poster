import fs from 'fs';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { homedir } from 'os';
import { resolve } from 'path';

let host = 'digital-movie-poster.test';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    //server: detectServerConfig(host),
    server: host,
    resolve: {
        alias: {
            '@': '/resources/js',
            vue: 'vue/dist/vue.esm-bundler.js', // TODO . REMOVE THIS AFTER INTERTIA
        },
    },
});

function detectServerConfig(host) {
    let keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`);
    let certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`);

    if (!fs.existsSync(keyPath)) {
        return {};
    }

    if (!fs.existsSync(certificatePath)) {
        return {};
    }

    return {
        hmr: { host },
        host,
        /*https: {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certificatePath),
        },*/
    };
}
