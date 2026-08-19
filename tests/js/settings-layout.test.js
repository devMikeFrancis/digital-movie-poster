import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import settingsForm from '@/mixins/settings-form';

/**
 * The split of the settings form across two screens.
 *
 * What matters here is that every control still has a home and that no control
 * ended up with two: the reorganisation moved thirty-eight of them, and a
 * setting silently dropped on the floor is invisible until someone goes looking
 * for the option they used to have.
 *
 * The pages are read as source rather than mounted. Mounting them means mocking
 * the settings endpoint, the auth store and the router for every assertion, and
 * would still only tell us which fields render for one particular set of
 * values - half of these are behind a v-if.
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath, URL } from 'node:url';

function read(path) {
    return readFileSync(fileURLToPath(new URL('../../' + path, import.meta.url)), 'utf8');
}

const display = read('resources/js/Views/Display.vue');
const settings = read('resources/js/Views/Settings.vue');

/** Every setting the page binds a control to. Not the page's own state. */
function bound(source) {
    return [...source.matchAll(/v-model="\s*settings\.([a-zA-Z_0-9]+)/g)].map((m) => m[1]);
}

/** The panel each setting sits in, keyed by the v-show that opens it. */
function tabOf(source, setting) {
    const at = source.indexOf(`settings.${setting}"`);
    const before = source.slice(0, at);
    const panels = [...before.matchAll(/v-show="tab === '([a-z]+)'"/g)];

    return panels.length ? panels[panels.length - 1][1] : null;
}

describe('every setting has exactly one home', () => {
    it('does not bind the same setting on both pages', () => {
        const shared = bound(display).filter((name) => bound(settings).includes(name));

        expect(shared).toEqual([]);
    });

    it('does not bind the same setting twice on one page', () => {
        [display, settings].forEach((source) => {
            const seen = bound(source);
            const duplicated = seen.filter((name, i) => seen.indexOf(name) !== i);

            expect(duplicated).toEqual([]);
        });
    });

    /**
     * Regression: the four tabs held sixty-five controls between them, and the
     * only way to be sure none was lost in the move is to name them.
     */
    it('keeps every setting the old form had', () => {
        const carried = [
            // Display, slideshow.
            'random_order',
            'transition_type',
            'poster_display_speed',
            'poster_fill_screen',
            'poster_fill_scrim',
            'remove_black_bars',
            'poster_bg_color',
            'mpaa_limit',
            'tv_limit',
            'play_theme_music',
            // Display, header and name.
            'show_header_text',
            'header_style',
            'header_position',
            'header_full_width',
            'coming_soon_text',
            'now_playing_text',
            'header_font',
            'header_font_size',
            'header_bg_color',
            'header_text_color',
            'header_border_color',
            'show_theater_name',
            'theater_name',
            'theater_name_position',
            'theater_name_style',
            'theater_name_full_width',
            // Display, poster details.
            'show_runtime',
            'show_mpaa_rating',
            'show_audience_rating',
            'show_speaker_config',
            'speaker_config',
            'show_processing_logos',
            'show_dolby_51',
            'show_dolby_atmos_vertical',
            'show_dolby_vision_vertical',
            'show_dts',
            'show_imax',
            'show_auro_3d',
            'footer_bg_color',
            'footer_text_color',
            // Settings.
            'validate_movie_titles',
            'tmdb_api_key_v3',
            'plex_service',
            'plex_ip_address',
            'plex_token',
            'plex_show_movie_now_playing',
            'plex_show_tv_now_playing',
            'plex_sync_movies',
            'plex_sync_tv',
            'jellyfin_service',
            'jellyfin_ip_address',
            'jellyfin_token',
            'kodi_service',
            'kodi_url',
            'kodi_port',
            'kodi_username',
            'kodi_password',
            'use_cec_power',
            'start_power_time',
            'end_power_time',
            'require_login',
        ];

        const everywhere = [...bound(display), ...bound(settings)];
        const missing = carried.filter((name) => !everywhere.includes(name));

        expect(missing).toEqual([]);
    });

    /** The two flags behind the one "which logos" question. */
    it('still writes both prologo flags, through the single control', () => {
        expect(display).toContain('use_global_prologos');
        expect(display).toContain('use_global_prologos_if_no_poster_prologos');
    });

    it('binds no setting the model no longer has', () => {
        const dropped = [
            'show_header_border',
            'poster_display_limit',
            'show_dolby_atmos_horizontal',
            'show_dolby_vision_horizontal',
            'tmdb_api_key_v4',
        ];

        dropped.forEach((name) => {
            expect(display).not.toContain(`settings.${name}`);
            expect(settings).not.toContain(`settings.${name}`);
        });
    });
});

describe('where each setting landed', () => {
    it.each([
        ['transition_type', 'slideshow'],
        ['poster_display_speed', 'slideshow'],
        ['poster_bg_color', 'slideshow'],
        ['mpaa_limit', 'slideshow'],
        // The header's appearance now sits with the header, not three clicks
        // away on a Theme tab the documentation had to point at.
        ['header_font', 'header'],
        ['header_bg_color', 'header'],
        ['coming_soon_text', 'header'],
        ['theater_name_style', 'header'],
        ['speaker_config', 'details'],
        ['footer_text_color', 'details'],
    ])('puts %s on the %s tab of Display', (setting, tab) => {
        expect(tabOf(display, setting)).toBe(tab);
    });

    it.each([
        // Sync configuration, which sat on the display tab away from every
        // other sync setting.
        ['validate_movie_titles', 'sources'],
        ['plex_token', 'sources'],
        ['use_cec_power', 'power'],
        ['start_power_time', 'power'],
        ['require_login', 'account'],
    ])('puts %s on the %s tab of Settings', (setting, tab) => {
        expect(tabOf(settings, setting)).toBe(tab);
    });
});

describe('a control that gates others', () => {
    /**
     * Regression: "Show speaker config" sat below the two settings it turns on,
     * and the power times were on show whether or not the schedule was.
     */
    it.each([
        [display, 'show_speaker_config', 'speaker-config'],
        [display, 'show_processing_logos', 'prologo-source'],
        [settings, 'use_cec_power', 'start-power-time'],
    ])('comes before what it controls and hides it', (source, gate, gated) => {
        // Matched on the whole id, because "speaker-config" is also a substring
        // of "show-speaker-config" and a looser check passes on the gate itself.
        expect(source.indexOf(`v-model="settings.${gate}"`)).toBeLessThan(
            source.indexOf(`id="${gated}"`),
        );
        expect(source).toContain(`v-if="settings.${gate}"`);
    });
});

describe('the form behaviour both pages share', () => {
    const form = {
        ...settingsForm,
        template: '<div />',
        data() {
            return { ...settingsForm.data(), tabs: [{ id: 'one' }, { id: 'two' }] };
        },
    };

    function mountForm(query = {}) {
        const pinia = createPinia();
        setActivePinia(pinia);

        return mount(form, {
            global: {
                plugins: [pinia],
                mocks: { $route: { query }, $router: { replace: vi.fn() } },
            },
        });
    }

    beforeEach(() => setActivePinia(createPinia()));

    it('opens the tab named in the address bar', () => {
        expect(mountForm({ tab: 'two' }).vm.tab).toBe('two');
    });

    it('falls back to the first tab when the address names one that is gone', () => {
        expect(mountForm({ tab: 'theme' }).vm.tab).toBe('one');
    });

    it('opens the first tab when the address names none', () => {
        expect(mountForm().vm.tab).toBe('one');
    });

    /**
     * Regression risk introduced by putting the tab in the URL: switching tabs
     * is now a route change, and the unsaved-changes guard would have caught it
     * and demanded to know whether you wanted to save first.
     */
    it('lets a tab switch through even with unsaved changes', () => {
        const vm = mountForm().vm;
        vm.savedSnapshot = '{"a":1}';
        const next = vi.fn();

        vm.confirmLeave({ path: '/display' }, { path: '/display' }, next);

        expect(next).toHaveBeenCalledWith();
        expect(vm.pendingLeave).toBe(null);
    });

    it('holds back a move to another screen with unsaved changes', () => {
        const vm = mountForm().vm;
        vm.savedSnapshot = '{"a":1}';
        const next = vi.fn();

        vm.confirmLeave({ path: '/posters' }, { path: '/display' }, next);

        expect(next).not.toHaveBeenCalled();
        expect(typeof vm.pendingLeave).toBe('function');
    });

    it('lets a move to another screen through when nothing is unsaved', () => {
        const vm = mountForm().vm;
        const next = vi.fn();

        vm.confirmLeave({ path: '/posters' }, { path: '/display' }, next);

        expect(next).toHaveBeenCalled();
        expect(vm.pendingLeave).toBe(null);
    });
});
