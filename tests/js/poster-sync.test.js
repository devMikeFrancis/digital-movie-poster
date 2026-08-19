import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), emit: vi.fn() })) }));

const get = vi.fn();
vi.mock('axios', () => ({ default: { get: (...args) => get(...args) } }));

import { usePostersStore } from '@/store/posters';

function library(count) {
    return Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        media_type: 'movie',
        mpaa_rating: 'PG',
        show: false,
        runtime: 100,
        show_runtime: true,
    }));
}

/**
 * Regression: a running display never picked up a poster added after it
 * started. The re-sync interval read 60000 * 60 * 60 * 1000 * 4 with a comment
 * saying every four hours - it came to a little over twenty-seven years, so the
 * screen kept cycling whatever was in the library the last time it loaded.
 */
describe('keeping the display in step with the library', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
    });

    afterEach(() => vi.useRealTimers());

    it('schedules the next pull four hours out, not decades out', () => {
        const store = usePostersStore();
        const setInterval = vi.spyOn(globalThis, 'setInterval');

        store.startSyncPosters();

        const [, delay] = setInterval.mock.calls[0];
        const hours = delay / 1000 / 60 / 60;

        expect(hours).toBe(4);
    });

    it('pulls the library when that comes round', () => {
        const store = usePostersStore();
        store.cachePosters = vi.fn();

        store.startSyncPosters();
        vi.advanceTimersByTime(1000 * 60 * 60 * 4);

        expect(store.cachePosters).toHaveBeenCalledTimes(1);
    });

    it('asks the display to reload so a saved poster appears without a second step', () => {
        const store = usePostersStore();
        const emit = vi.fn();
        store.socket = { emit };

        store.requestDisplayReload();

        expect(emit).toHaveBeenCalledWith('dispatch:command', { command: 'reload' });
    });

    it('keeps a poster on screen across a routine sync', async () => {
        // The fetched list has nothing marked as showing. Swapping it in and
        // walking away left the screen empty until the next change - and since
        // the transitions had been stopped and were only restarted while
        // booting, there was no next change.
        const store = usePostersStore();
        store.settings = {
            random_order: false,
            mpaa_limit: '',
            tv_limit: '',
            poster_display_speed: 15000,
        };
        store.moviePosters = library(3);
        store.loading = false;
        get.mockResolvedValue({ data: { posters: library(5) } });

        await store.cachePosters();

        expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
    });

    it('starts the clock again after a routine sync', async () => {
        const store = usePostersStore();
        store.settings = {
            random_order: false,
            mpaa_limit: '',
            tv_limit: '',
            poster_display_speed: 15000,
        };
        store.moviePosters = library(3);
        store.loading = false;
        get.mockResolvedValue({ data: { posters: library(5) } });

        await store.cachePosters();
        store.transitionImages = vi.fn();
        vi.advanceTimersByTime(15000);

        expect(window.transitionImagesInterval).toBeTruthy();
    });

    it('does not leave a second clock running when started twice', () => {
        const store = usePostersStore();
        store.settings = { poster_display_speed: 15000 };
        store.transitionImages = vi.fn();

        store.startTransitionImages();
        const first = window.transitionImagesInterval;
        store.startTransitionImages();

        vi.advanceTimersByTime(15000);

        expect(window.transitionImagesInterval).not.toBe(first);
        expect(store.transitionImages).toHaveBeenCalledTimes(1);
    });

    it('does nothing when there is no socket to ask', () => {
        const store = usePostersStore();
        store.socket = '';

        expect(() => store.requestDisplayReload()).not.toThrow();
    });
});
