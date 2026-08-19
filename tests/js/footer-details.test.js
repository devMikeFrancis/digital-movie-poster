import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { usePostersStore } from '@/store/posters';
import BottomFooter from '@/components/bottom-footer.vue';
import TopHeader from '@/components/top-header.vue';
import ProcessingLogos from '@/components/processing-logos.vue';

/**
 * The top of the screen carries the header wording and the theatre name; the
 * runtime and the speaker badge belong with the rest of what describes the
 * poster, in the footer.
 *
 * Both used to float over the header, positioned rather than laid out in the
 * row, which is what a header spread across the width kept running into.
 */
function footer(settings, state = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    Object.assign(usePostersStore(), {
        settings: { transition_type: 'fade', ...settings },
        ...state,
    });

    return mount(BottomFooter, {
        global: { plugins: [pinia], stubs: { StarRating: true } },
    });
}

function header(settings, state = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    Object.assign(usePostersStore(), {
        settings: { transition_type: 'fade', ...settings },
        ...state,
    });

    return mount(TopHeader, { global: { plugins: [pinia] } });
}

describe('the runtime', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('is drawn in the footer', () => {
        const shown = footer({ show_runtime: true }, { runtime: 119 });

        expect(shown.find('.runtime').text()).toBe('119 min');
    });

    it('is not drawn in the header any more', () => {
        const shown = header({ show_runtime: true, show_header_text: true }, { runtime: 119 });

        expect(shown.find('.runtime').exists()).toBe(false);
    });

    it('follows the now-playing film when one is on', () => {
        const shown = footer(
            { show_runtime: true },
            { runtime: 119, nowPlayingRuntime: 92, nowPlaying: true },
        );

        expect(shown.find('.runtime').text()).toBe('92 min');
    });

    it('stays away when the setting is off', () => {
        expect(footer({ show_runtime: false }, { runtime: 119 }).find('.runtime').exists()).toBe(
            false,
        );
    });

    /** Nothing to say before the first poster has been shown. */
    it('stays away when there is no runtime to show', () => {
        expect(footer({ show_runtime: true }, { runtime: 0 }).find('.runtime').exists()).toBe(
            false,
        );
    });
});

describe('the speaker badge', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('is drawn in the footer', () => {
        const shown = footer({ show_speaker_config: true, speaker_config: '7.1.4' });

        expect(shown.find('.speaker-config-text').text()).toBe('7.1.4');
    });

    /**
     * Regression: it was rendered inside the processing-logos component, whose
     * root is behind the logos setting - so switching the logos off took the
     * speaker badge with it, whatever the badge's own setting said.
     */
    it('survives the processing logos being switched off', () => {
        const shown = footer({
            show_speaker_config: true,
            speaker_config: '7.1.4',
            show_processing_logos: false,
        });

        expect(shown.findComponent(ProcessingLogos).find('.dolby-logos').exists()).toBe(false);
        expect(shown.find('.speaker-config-text').text()).toBe('7.1.4');
    });

    it('is not drawn in the header any more', () => {
        const shown = header({ show_speaker_config: true, speaker_config: '7.1.4' });

        expect(shown.find('.speaker-config-text').exists()).toBe(false);
    });

    it('stays away when the setting is off', () => {
        expect(footer({ show_speaker_config: false }).find('.speaker-config').exists()).toBe(false);
    });
});
