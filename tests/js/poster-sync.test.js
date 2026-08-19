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
        store.reloadMoviePosters = vi.fn();

        store.startSyncPosters();
        vi.advanceTimersByTime(1000 * 60 * 60 * 4);

        expect(store.reloadMoviePosters).toHaveBeenCalledTimes(1);
    });

    it('re-reads the library from an endpoint the display is allowed to call', () => {
        // It used to call /api/cache-posters, which sits behind the admin
        // session and queues a media-server sync - so the display got a 401,
        // and the response carries no posters even when it succeeds.
        const store = usePostersStore();
        store.reloadMoviePosters = vi.fn();

        store.startSyncPosters();
        vi.advanceTimersByTime(1000 * 60 * 60 * 4);

        expect(store.reloadMoviePosters).toHaveBeenCalled();
    });

    it('asks the display to reload so a saved poster appears without a second step', () => {
        const store = usePostersStore();
        const emit = vi.fn();
        store.socket = { emit };

        store.requestDisplayReload();

        expect(emit).toHaveBeenCalledWith('dispatch:command', { command: 'reload' });
    });

    it('keeps the poster on screen up across a routine refresh', async () => {
        const store = usePostersStore();
        store.settings = { random_order: false, mpaa_limit: '', tv_limit: '' };
        store.moviePosters = library(3);
        store.currentPosterId = 2;
        store.loading = false;
        get.mockResolvedValue({ data: { posters: library(5) } });

        await store.reloadMoviePosters();

        const showing = store.mediaPosters.filter((p) => p.show);
        expect(showing).toHaveLength(1);
        expect(showing[0].id).toBe(2);
    });

    it('puts a poster up when the one on screen has left the rotation', async () => {
        const store = usePostersStore();
        store.settings = { random_order: false, mpaa_limit: '', tv_limit: '' };
        store.moviePosters = library(3);
        store.currentPosterId = 99;
        store.loading = false;
        get.mockResolvedValue({ data: { posters: library(5) } });

        await store.reloadMoviePosters();

        expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
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
