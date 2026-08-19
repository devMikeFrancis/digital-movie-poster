import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), disconnect: vi.fn() })) }));

import { usePostersStore } from '@/store/posters';

/**
 * Regression: the display occasionally went blank.
 *
 * In random order the next poster could be the one already on screen. The old
 * code then set show on it and cleared show on the poster it was replacing -
 * the same object - so it ended up hidden and nothing was left showing until
 * the next change came round.
 */
function storeWith(posters, settings = {}) {
    setActivePinia(createPinia());

    const store = usePostersStore();
    store.moviePosters = posters;
    store.settings = { random_order: true, mpaa_limit: '', tv_limit: '', ...settings };

    return store;
}

function library(count) {
    return Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        name: `Poster ${i + 1}`,
        media_type: 'movie',
        mpaa_rating: 'PG',
        show: i === 0,
    }));
}

/** Force the random pick onto a chosen index. */
function pickIndex(index, length) {
    vi.spyOn(Math, 'random').mockReturnValue(index / length);
}

describe('choosing the next poster', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('always leaves exactly one poster showing when random picks the current one', () => {
        const store = storeWith(library(4));
        pickIndex(0, 4); // the poster already on screen

        store.getInSequencePoster();

        expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
    });

    it('never returns nothing to show', () => {
        const store = storeWith(library(4));

        for (let i = 0; i < 4; i++) {
            pickIndex(i, 4);
            const poster = store.getInSequencePoster();

            expect(poster, `picking index ${i} returned nothing`).toBeTruthy();
            expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
        }
    });

    it('moves on rather than sitting on the same poster', () => {
        const store = storeWith(library(4));
        pickIndex(0, 4);

        const poster = store.getInSequencePoster();

        expect(poster.id).not.toBe(1);
    });

    it('stays on the only poster when there is just one', () => {
        const store = storeWith(library(1));

        const poster = store.getInSequencePoster();

        expect(poster.id).toBe(1);
        expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
    });

    it('picks within the posters actually on show, not the whole library', () => {
        // A rating limit makes the shown list shorter than the library; the
        // random index used to be drawn against the library length, so it could
        // land past the end and leave nothing showing.
        const posters = library(6);
        posters.slice(3).forEach((p) => (p.mpaa_rating = 'R'));
        const store = storeWith(posters, { mpaa_limit: 'PG' });

        expect(store.mediaPosters).toHaveLength(3);

        for (let i = 0; i < 20; i++) {
            vi.spyOn(Math, 'random').mockReturnValue(0.95);
            const poster = store.getInSequencePoster();

            expect(poster, 'picked past the end of the shown posters').toBeTruthy();
            expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
        }
    });

    it('does not fall over when a rating limit leaves nothing to show', () => {
        const posters = library(3);
        posters.forEach((p) => (p.mpaa_rating = 'R'));
        posters.forEach((p) => (p.show = false));
        const store = storeWith(posters, { mpaa_limit: 'G' });

        expect(store.mediaPosters).toHaveLength(0);
        expect(() => store.setInitialPosterView()).not.toThrow();
        expect(store.getInSequencePoster()).toBeNull();
    });

    it('walks in order when random order is off', () => {
        const store = storeWith(library(3), { random_order: false });

        expect(store.getInSequencePoster().id).toBe(2);
        expect(store.getInSequencePoster().id).toBe(3);
        expect(store.getInSequencePoster().id).toBe(1);
    });
});
