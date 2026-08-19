import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: {
        get: vi.fn(() => Promise.resolve({ data: {} })),
        post: vi.fn(() => Promise.resolve({ data: {} })),
    },
}));

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), disconnect: vi.fn() })) }));

import Settings from '@/Views/Settings.vue';

/**
 * Regression: a select bound to null matches no option - not the one whose
 * value is the empty string - so the rating limits rendered blank on any
 * install that had never set one, and the field looked broken. Nothing about
 * what is stored changes; null and '' both mean no limit.
 */
function settings() {
    return mount(Settings, {
        global: {
            plugins: [createPinia()],
            stubs: { MainNav: true, 'router-link': true },
        },
    });
}

describe('settings that reach a select', () => {
    it('turns a null rating limit into the None option', () => {
        const filled = settings().vm.withSelectDefaults({ mpaa_limit: null, tv_limit: null });

        expect(filled.mpaa_limit).toBe('');
        expect(filled.tv_limit).toBe('');
    });

    it('turns a missing rating limit into the None option', () => {
        const filled = settings().vm.withSelectDefaults({});

        expect(filled.mpaa_limit).toBe('');
        expect(filled.tv_limit).toBe('');
    });

    it('leaves a real rating limit alone', () => {
        const filled = settings().vm.withSelectDefaults({ mpaa_limit: 'PG-13', tv_limit: 'TV-14' });

        expect(filled.mpaa_limit).toBe('PG-13');
        expect(filled.tv_limit).toBe('TV-14');
    });

    it('defaults the other selects rather than letting them render blank', () => {
        const filled = settings().vm.withSelectDefaults({});

        expect(filled.theater_name_position).toBe('bottom');
        expect(filled.poster_fill_scrim).toBe('standard');
        expect(filled.transition_type).toBe('fade');
    });

    it('leaves those alone when they are already set', () => {
        const filled = settings().vm.withSelectDefaults({
            theater_name_position: 'top',
            poster_fill_scrim: 'none',
            transition_type: 'crossfade',
        });

        expect(filled.theater_name_position).toBe('top');
        expect(filled.poster_fill_scrim).toBe('none');
        expect(filled.transition_type).toBe('crossfade');
    });
});
