import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

/**
 * Separate from vite.config.js on purpose.
 *
 * The build config is driven by laravel-vite-plugin, which expects to be
 * building into public/build against a running Laravel app - none of which
 * applies to a test run, and which makes the plugin complain. This keeps the
 * same '@' alias and Vue handling without dragging that in.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        setupFiles: ['./tests/js/setup.js'],
        restoreMocks: true,
    },
});
