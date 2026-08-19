import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { readFileSync } from 'node:fs';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { get: vi.fn(() => Promise.resolve({ data: {} })), post: vi.fn() },
}));
vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), disconnect: vi.fn() })) }));

import Settings from '@/Views/Settings.vue';

/**
 * The two flags behind this are one decision, and as a pair of checkboxes they
 * did not say what they did - the first overrides every title, which is not
 * what "use logos from global settings" sounds like. A film with no Atmos
 * soundtrack showing the Atmos logo is that setting working as written.
 */
function settingsScreen() {
    return mount(Settings, {
        global: { plugins: [createPinia()], stubs: { MainNav: true, 'router-link': true } },
    });
}

describe('which processing logos to show', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it.each([
        ['poster', false, false],
        ['poster-then-global', false, true],
        ['global', true, false],
    ])('%s maps onto the two stored flags', (choice, useGlobal, fallBack) => {
        const vm = settingsScreen().vm;

        vm.prologoSource = choice;

        expect(vm.settings.use_global_prologos).toBe(useGlobal);
        expect(vm.settings.use_global_prologos_if_no_poster_prologos).toBe(fallBack);
    });

    it.each([
        [{ use_global_prologos: true, use_global_prologos_if_no_poster_prologos: false }, 'global'],
        [
            { use_global_prologos: false, use_global_prologos_if_no_poster_prologos: true },
            'poster-then-global',
        ],
        [
            { use_global_prologos: false, use_global_prologos_if_no_poster_prologos: false },
            'poster',
        ],
    ])('reads back what is stored', (stored, expected) => {
        const vm = settingsScreen().vm;

        Object.assign(vm.settings, stored);

        expect(vm.prologoSource).toBe(expected);
    });

    it('leaves nothing opaque behind the Dolby Vision logo', () => {
        // The bounding box exported with this logo kept its fill:none in the
        // standalone file's stylesheet, which did not travel with the markup -
        // so inlined it fell back to the SVG default of black.
        const source = readFileSync('resources/js/components/processing-logos.vue', 'utf8');

        expect(source).not.toContain('class="cls-1"');
    });

    it('opens each logo with something that has a fill', () => {
        // The one that broke was a bounding box sitting directly inside <svg>,
        // where there is no group fill to inherit. Shapes deeper in the markup
        // sit inside a g fill="none" and are fine, so this checks the position
        // that actually went wrong rather than guessing at ancestry.
        const source = readFileSync('resources/js/components/processing-logos.vue', 'utf8');

        const bare = [...source.matchAll(/<svg\b[^>]*>\s*<(rect|path|polygon)\b([^>]*)>/g)].filter(
            (m) => !/fill|:style|style=/.test(m[2])
        );

        expect(bare.map((m) => m[0].slice(-70))).toEqual([]);
    });
});
