import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), disconnect: vi.fn() })) }));

import { usePostersStore } from '@/store/posters';

/**
 * Regression: the strictest option in each list - G and TV-Y - read an
 * undefined variable instead of the rating handed in. Choosing either threw
 * inside the filter that builds the poster list, which took the whole list with
 * it and left the display blank. They are also the options a parent is most
 * likely to pick.
 */
function store(settings) {
    setActivePinia(createPinia());

    const s = usePostersStore();
    s.settings = { mpaa_limit: '', tv_limit: '', ...settings };

    return s;
}

describe('rating display limits', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it.each([
        ['G', 'G', true],
        ['G', 'PG', false],
        ['G', 'R', false],
        ['PG', 'G', true],
        ['PG', 'PG', true],
        ['PG', 'PG-13', false],
        ['PG-13', 'PG-13', true],
        ['PG-13', 'R', false],
        ['R', 'R', true],
        ['R', 'NC-17', false],
    ])('limit %s %s allow %s', (limit, rating, allowed) => {
        expect(store({ mpaa_limit: limit }).withinMpaaLimit(rating)).toBe(allowed);
    });

    it.each([
        ['TV-Y', 'TV-Y', true],
        ['TV-Y', 'TV-G', false],
        ['TV-Y', 'TV-MA', false],
        ['TV-PG', 'TV-G', true],
        ['TV-PG', 'TV-14', false],
        ['TV-14', 'TV-14', true],
        ['TV-14', 'TV-MA', false],
    ])('TV limit %s %s allow %s', (limit, rating, allowed) => {
        expect(store({ tv_limit: limit }).withinTvLimit(rating)).toBe(allowed);
    });

    it('allows everything when no limit is set', () => {
        const s = store({});

        expect(s.withinMpaaLimit('NC-17')).toBe(true);
        expect(s.withinTvLimit('TV-MA')).toBe(true);
    });

    it('builds the poster list without throwing on the strictest limits', () => {
        for (const settings of [{ mpaa_limit: 'G' }, { tv_limit: 'TV-Y' }]) {
            const s = store(settings);
            s.moviePosters = [
                { id: 1, media_type: 'movie', mpaa_rating: 'G', show: false },
                { id: 2, media_type: 'tv', mpaa_rating: 'TV-Y', show: false },
                { id: 3, media_type: 'movie', mpaa_rating: 'R', show: false },
            ];

            expect(() => s.mediaPosters).not.toThrow();
        }
    });
});
