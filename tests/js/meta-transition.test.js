import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

/**
 * Regression: the poster's cross-fade holds the outgoing image at full opacity
 * while the incoming one covers it. That works because a poster covers a
 * poster. The details around it — the runtime, the content rating, the logos
 * and the stars — are centred text and icons of different widths, so nothing
 * covered anything and the old and new sets were readable at the same time.
 *
 * out-in is what makes them swap instead: the old details leave before the new
 * ones arrive.
 *
 * The runtime used to have a Transition of its own in the header doing the same
 * job. It sits in the footer row now and rides that one.
 *
 * These are structural assertions rather than behavioural ones, and that is a
 * real limit. jsdom gives transitions no duration, so Vue settles them
 * immediately and a mounted component shows one set either way — the overlap
 * simply cannot be reproduced here. What can be pinned is the declaration that
 * prevents it, and the timings that keep the swap inside the poster's change.
 */
const components = {
    'the footer details': 'resources/js/components/bottom-footer.vue',
};

describe('the details around the poster', () => {
    it.each(Object.entries(components))('%s swap rather than overlap', (_name, path) => {
        const source = readFileSync(path, 'utf8');

        expect(source).toMatch(/<Transition[^>]*mode="out-in"/);
    });

    it.each(Object.entries(components))(
        '%s do not hold the outgoing set at full opacity',
        (_name, path) => {
            const source = readFileSync(path, 'utf8');

            // The poster's trick: hold, then drop once covered. Nothing covers
            // these, so holding leaves both readable at once.
            expect(source).not.toMatch(/crossfade-meta-leave-active[\s\S]{0,120}?linear\s+1\.6s/);
        },
    );

    it.each(Object.entries(components))('%s finish inside the poster change', (_name, path) => {
        const source = readFileSync(path, 'utf8');
        const durations = [
            ...source.matchAll(/-meta-(?:enter|leave)-active[\s\S]{0,160}?([\d.]+)s/g),
        ]
            .map((m) => Number(m[1]))
            .filter((n) => n > 0);

        expect(durations.length).toBeGreaterThan(0);

        // Out and in run back to back, so the pair has to fit inside the
        // shortest poster transition, which is the 1.6s cross-fade.
        expect(Math.max(...durations) * 2).toBeLessThanOrEqual(1.6);
    });
});
