import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), emit: vi.fn() })) }));

import { usePostersStore } from '@/store/posters';
import Dashboard from '@/Views/Dashboard.vue';

/**
 * The shading behind the header and footer exists to keep text readable over
 * artwork. An end with nothing on it does not need any — a display showing only
 * a poster was getting a dark band across the top and bottom of it regardless.
 */
function dashboard(settings) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const store = usePostersStore();
    store.settings = {
        poster_fill_screen: true,
        poster_fill_scrim: 'standard',
        transition_type: 'fade',
        show_header_text: false,
        show_runtime: false,
        show_speaker_config: false,
        show_mpaa_rating: false,
        show_processing_logos: false,
        show_audience_rating: false,
        show_theater_name: false,
        header_position: 'top',
        ...settings,
    };
    store.loading = false;

    return mount(Dashboard, {
        global: {
            plugins: [pinia],
            stubs: { TopHeader: true, BottomFooter: true, TheaterName: true, VotingScreen: true },
        },
    }).vm;
}

const sides = (vm) => ({ top: vm.topHasContent, bottom: vm.bottomHasContent });

describe('shading only where there is something to shade', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('shades neither end of a display showing only artwork', () => {
        expect(sides(dashboard({}))).toEqual({ top: false, bottom: false });
    });

    it('shades the top for the header wording', () => {
        expect(sides(dashboard({ show_header_text: true }))).toEqual({ top: true, bottom: false });
    });

    it('shades the top for the runtime alone', () => {
        expect(sides(dashboard({ show_runtime: true }))).toEqual({ top: true, bottom: false });
    });

    it.each([
        ['the content rating', 'show_mpaa_rating'],
        ['the processing logos', 'show_processing_logos'],
        ['the audience rating', 'show_audience_rating'],
    ])('shades the bottom for %s', (_label, setting) => {
        expect(sides(dashboard({ [setting]: true }))).toEqual({ top: false, bottom: true });
    });

    it('follows the theatre name to whichever end it is on', () => {
        const shown = { show_theater_name: true, theater_name: 'The Roxy' };

        expect(sides(dashboard({ ...shown, theater_name_position: 'top' })))
            .toEqual({ top: true, bottom: false });
        expect(sides(dashboard({ ...shown, theater_name_position: 'bottom' })))
            .toEqual({ top: false, bottom: true });
    });

    it('follows the header to whichever end it is on', () => {
        expect(sides(dashboard({ show_header_text: true, header_position: 'bottom' })))
            .toEqual({ top: false, bottom: true });
    });

    it('follows the speaker config to its own corner', () => {
        const on = { show_speaker_config: true };

        expect(sides(dashboard({ ...on, speaker_config_location: 'top-right' })))
            .toEqual({ top: true, bottom: false });
        expect(sides(dashboard({ ...on, speaker_config_location: 'bottom' })))
            .toEqual({ top: false, bottom: true });
    });

    it('suppresses each end independently', () => {
        const vm = dashboard({ show_header_text: true });

        expect(vm.scrimSides).toContain('no-scrim-bottom');
        expect(vm.scrimSides).not.toContain('no-scrim-top');
    });

    it('does nothing outside fill mode, where the poster is boxed anyway', () => {
        const vm = dashboard({ poster_fill_screen: false });

        expect(vm.scrimSides).toBe('');
    });
});
