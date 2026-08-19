import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { availableWidth, solveSpacing } from '@/support/spread-text';
import { usePostersStore } from '@/store/posters';
import TheaterName from '@/components/theater-name.vue';
import TopHeader from '@/components/top-header.vue';

/**
 * Regression: "span the width of the screen" stretched the plate and left the
 * words where they were, so on a plain or neon plate the option did nothing you
 * could see - a 1228px box with 262px of text centred in it.
 *
 * The measuring itself needs layout, which jsdom does not do, so these drive
 * the solver with a stand-in for the browser's measurements and check the
 * components' own decisions about when to spread separately.
 */

/** A line of `characters` glyphs, each `glyph` wide, plus one gap per glyph. */
function line(characters, glyph) {
    return (spacing) => characters * glyph + characters * spacing;
}

function plate(component, settings) {
    const pinia = createPinia();
    setActivePinia(pinia);
    usePostersStore().settings = { transition_type: 'fade', ...settings };

    return mount(component, {
        global: { plugins: [pinia], stubs: { SpeakerConfig: true } },
    });
}

describe('solving for the spacing that fills a width', () => {
    it('lands on the width exactly', () => {
        const measure = line(15, 17);

        const spacing = solveSpacing(measure, 1228);

        expect(measure(spacing)).toBeCloseTo(1228, 6);
    });

    it('lands on the width exactly when the stylesheet already tracks the text', () => {
        const measure = line(15, 17);

        const spacing = solveSpacing(measure, 1228, 3);

        expect(measure(spacing)).toBeCloseTo(1228, 6);
    });

    it('leaves a line that is already too wide at the spacing it was given', () => {
        // A theatre name longer than the screen must not be squeezed tighter
        // than the design asks for to make it fit.
        expect(solveSpacing(line(90, 17), 1228, 3)).toBe(3);
    });

    it('leaves a hidden element alone', () => {
        // Everything measures zero before the display has laid out, and the
        // slope of nothing would set the spacing to Infinity.
        expect(solveSpacing(() => 0, 1228, 3)).toBe(3);
    });

    it('leaves the line alone when there is no width to fill', () => {
        expect(solveSpacing(line(15, 17), 0, 3)).toBe(3);
    });
});

describe('the width a plate has to offer', () => {
    it('is what is left after the decoration takes its padding', () => {
        const box = { clientWidth: 1228 };
        const view = {
            getComputedStyle: () => ({
                paddingLeft: '30px',
                paddingRight: '30px',
            }),
        };

        expect(availableWidth(box, view)).toBe(1168);
    });

    it('is the whole plate when the style has no padding', () => {
        const box = { clientWidth: 1228 };
        const view = {
            getComputedStyle: () => ({ paddingLeft: '', paddingRight: '' }),
        };

        expect(availableWidth(box, view)).toBe(1228);
    });
});

describe('which theatre name plates spread', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('spreads a plain plate, which has no decoration of its own to span', () => {
        const name = plate(TheaterName, {
            theater_name: 'Reference Level',
            theater_name_style: 'plain',
            theater_name_full_width: true,
        });

        expect(name.vm.plateSpreads).toBe(true);
    });

    it('spreads a neon plate', () => {
        const name = plate(TheaterName, {
            theater_name: 'Reference Level',
            theater_name_style: 'neon',
            theater_name_full_width: true,
        });

        expect(name.vm.plateSpreads).toBe(true);
    });

    it('leaves a rules plate alone, because the hairlines do the spanning', () => {
        const name = plate(TheaterName, {
            theater_name: 'Reference Level',
            theater_name_style: 'rules',
            theater_name_full_width: true,
        });

        expect(name.vm.plateSpreads).toBe(false);
    });

    it('does not spread a plate that was not asked to span', () => {
        const name = plate(TheaterName, {
            theater_name: 'Reference Level',
            theater_name_style: 'neon',
            theater_name_full_width: false,
        });

        expect(name.vm.plateSpreads).toBe(false);
    });

    it('marks the plate as full width so the decoration spans too', () => {
        const name = plate(TheaterName, {
            theater_name: 'Reference Level',
            theater_name_style: 'marquee',
            theater_name_full_width: true,
        });

        expect(name.find('.dmp-plate').classes()).toContain('dmp-plate--full');
    });
});

describe('the header keeping clear of the badges over it', () => {
    beforeEach(() => setActivePinia(createPinia()));

    /**
     * Regression: the runtime and the speaker badge are positioned over the
     * header rather than laid out in the row with it, so a line spread to the
     * full width ran straight underneath both of them.
     */
    it('reserves the runtime end when a spread header shows one', () => {
        const header = plate(TopHeader, {
            show_header_text: true,
            header_full_width: true,
            show_runtime: true,
        });

        expect(header.classes()).toContain('reserve-runtime');
    });

    it('reserves the speaker end when a spread header shows the badge', () => {
        const header = plate(TopHeader, {
            show_header_text: true,
            header_full_width: true,
            show_speaker_config: true,
            speaker_config_location: 'top-right',
        });

        expect(header.classes()).toContain('reserve-speaker');
    });

    it('does not reserve the speaker end when the badge sits under the poster', () => {
        const header = plate(TopHeader, {
            show_header_text: true,
            header_full_width: true,
            show_speaker_config: true,
            speaker_config_location: 'bottom',
        });

        expect(header.classes()).not.toContain('reserve-speaker');
    });

    it('reserves nothing on a header that hugs its words', () => {
        const header = plate(TopHeader, {
            show_header_text: true,
            header_full_width: false,
            show_runtime: true,
            show_speaker_config: true,
            speaker_config_location: 'top-right',
        });

        expect(header.classes()).not.toContain('reserve-runtime');
        expect(header.classes()).not.toContain('reserve-speaker');
    });
});
