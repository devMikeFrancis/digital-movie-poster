import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { usePostersStore } from '@/store/posters';

/**
 * The poster, the runtime and the footer details all read this one getter, so
 * they cannot disagree about which effect is running - they have to move
 * together to look like a single change.
 *
 * Regression: 2.1.3 added Cross-fade and Cut. Displays running older code
 * treated anything that was not 'fade' as the vertical slide, which is why
 * choosing Cross-fade appeared to do Vertical.
 */
describe('transitionPrefix', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it.each([
        ['fade', 'fade'],
        ['crossfade', 'crossfade'],
        ['vertical', 'slide'],
        ['cut', 'cut'],
    ])('maps %s to the %s classes', (setting, prefix) => {
        const store = usePostersStore();
        store.settings = { transition_type: setting };

        expect(store.transitionPrefix).toBe(prefix);
    });

    it('keeps the older slide classes for vertical so existing displays are unaffected', () => {
        const store = usePostersStore();
        store.settings = { transition_type: 'vertical' };

        expect(store.transitionPrefix).toBe('slide');
    });

    it('falls back to fade rather than to a slide when the value is unknown', () => {
        const store = usePostersStore();

        for (const value of ['barrel-roll', '', null, undefined]) {
            store.settings = { transition_type: value };

            expect(store.transitionPrefix).toBe('fade');
        }
    });
});
