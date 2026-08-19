import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { usePostersStore } from '@/store/posters';
import TopHeader from '@/components/top-header.vue';

/**
 * Regression: the settings API hands booleans back as 0 and 1, and the first
 * version of this test was `show_header_text !== false` - which 0 passes, so
 * the header stayed on when it had been switched off.
 */
function header(settings) {
    const pinia = createPinia();
    setActivePinia(pinia);
    usePostersStore().settings = { transition_type: 'fade', ...settings };

    return mount(TopHeader, {
        global: { plugins: [pinia], stubs: { SpeakerConfig: true } },
    });
}

describe('the Coming Soon / Now Playing text', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('is hidden when the setting comes back as 0', () => {
        expect(header({ show_header_text: 0 }).find('h1').exists()).toBe(false);
    });

    it('is hidden when the setting comes back as false', () => {
        expect(header({ show_header_text: false }).find('h1').exists()).toBe(false);
    });

    it('is shown when the setting comes back as 1', () => {
        expect(header({ show_header_text: 1 }).find('h1').exists()).toBe(true);
    });

    it('is shown when the setting comes back as true', () => {
        expect(header({ show_header_text: true }).find('h1').exists()).toBe(true);
    });

    it('is shown on an install that predates the option', () => {
        // Absent must not silently switch off a header that has always shown.
        expect(header({}).find('h1').exists()).toBe(true);
    });
});
