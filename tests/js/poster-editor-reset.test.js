import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { get: vi.fn(() => Promise.resolve({ data: { poster: {} } })), post: vi.fn() },
}));

import PostersEdit from '@/Views/PostersEdit.vue';

/**
 * Regression: "Reset to create new poster" blanked every property it found on
 * the poster object. By then the object is the API's poster - a successful save
 * replaces the form's copy with the response - so it cleared a shape the form
 * was never written for, leaving media_type and the switches as empty strings.
 * The next save came back 422 for a missing media type, with a blank field and
 * nothing on screen to explain it.
 */
function editor() {
    return mount(PostersEdit, {
        global: {
            plugins: [createPinia()],
            stubs: { MainNav: true, TitleLookup: true, 'router-link': true },
            mocks: { $route: { params: { id: '0' } } },
        },
    });
}

/** What the API hands back, and what savePoster assigns on success. */
const savedPoster = {
    id: 7,
    object_id: null,
    name: 'Back to the Future',
    file_name: 'back-to-the-future.webp',
    show_in_rotation: true,
    ordinal: 0,
    created_at: '2026-08-19T00:00:00Z',
    updated_at: '2026-08-19T00:00:00Z',
    can_delete: true,
    imdb_id: 'tt0088763',
    mpaa_rating: 'PG',
    audience_rating: 8.3,
    trailer_path: 'abc',
    runtime: 116,
    show_runtime: true,
    show_trailer: false,
    theme_music_path: null,
    play_theme_music: false,
    show_dolby_atmos: true,
    show_dolby_51: false,
    show_dolby_vision: false,
    show_dtsx: false,
    show_auro_3d: false,
    show_imax: false,
    media_type: 'movie',
    title: 'Back to the Future',
    image: null,
};

describe('the poster editor reset', () => {
    it('leaves a media type set, which the save requires', () => {
        const wrapper = editor();
        wrapper.vm.poster = { ...savedPoster };
        wrapper.vm.mode = 'edit';

        wrapper.vm.clearPoster();

        expect(wrapper.vm.poster.media_type).toBe('movie');
        expect(wrapper.vm.poster.media_type).not.toBe('');
    });

    it('leaves the switches as booleans rather than empty strings', () => {
        const wrapper = editor();
        wrapper.vm.poster = { ...savedPoster };

        wrapper.vm.clearPoster();

        for (const flag of [
            'show_trailer',
            'show_runtime',
            'show_in_rotation',
            'play_theme_music',
            'show_dolby_atmos',
            'show_dolby_51',
            'show_dolby_vision',
            'show_dtsx',
            'show_auro_3d',
            'show_imax',
        ]) {
            expect(typeof wrapper.vm.poster[flag], `${flag} should be a boolean`).toBe('boolean');
        }
    });

    it('clears what was typed and drops back to creating', () => {
        const wrapper = editor();
        wrapper.vm.poster = { ...savedPoster };
        wrapper.vm.mode = 'edit';

        wrapper.vm.clearPoster();

        expect(wrapper.vm.poster.id).toBe(0);
        expect(wrapper.vm.poster.name).toBe('');
        expect(wrapper.vm.poster.imdb_id).toBe('');
        expect(wrapper.vm.poster.image).toBeNull();
        expect(wrapper.vm.mode).toBe('new');
        expect(wrapper.vm.savePosterBtn).toBe('Create Poster');
    });

    it('does not carry the API-only fields into the new poster', () => {
        const wrapper = editor();
        wrapper.vm.poster = { ...savedPoster };

        wrapper.vm.clearPoster();

        for (const field of ['file_name', 'ordinal', 'created_at', 'can_delete']) {
            expect(wrapper.vm.poster).not.toHaveProperty(field);
        }
    });

    it('rebuilds the file inputs so a previous filename is not left showing', () => {
        const wrapper = editor();
        const before = wrapper.vm.formGeneration;

        wrapper.vm.clearPoster();

        expect(wrapper.vm.formGeneration).toBe(before + 1);
    });
});
