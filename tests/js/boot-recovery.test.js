import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), emit: vi.fn() })) }));

const get = vi.fn();
vi.mock('axios', () => ({ default: { get: (...args) => get(...args) } }));

import { usePostersStore } from '@/store/posters';

/**
 * A Raspberry Pi routinely reaches the browser before the web server is
 * answering. The first poster load stops the slideshow before it asks, and
 * nothing else starts it — so a failure there used to leave the display on its
 * loading screen for good, with only the message to say why.
 */
describe('recovering from a failed first load', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
    });

    afterEach(() => vi.useRealTimers());

    it('tries again rather than sitting on the loading screen', async () => {
        const store = usePostersStore();
        store.settings = { random_order: false, mpaa_limit: '', tv_limit: '' };
        get.mockRejectedValueOnce(new Error('Network Error'));

        await store.getMoviePosters();
        expect(get).toHaveBeenCalledTimes(1);

        get.mockResolvedValueOnce({ data: { posters: [] } });
        await vi.advanceTimersByTimeAsync(5000);

        expect(get).toHaveBeenCalledTimes(2);
    });

    it('says what it is waiting for while it retries', async () => {
        const store = usePostersStore();
        get.mockRejectedValueOnce(new Error('Network Error'));

        await store.getMoviePosters();

        expect(store.loadingMessage).toMatch(/waiting for dmp/i);
    });

    it('comes up properly once the server answers', async () => {
        const store = usePostersStore();
        store.settings = { random_order: false, mpaa_limit: '', tv_limit: '' };
        get.mockRejectedValueOnce(new Error('Network Error'));

        await store.getMoviePosters();

        get.mockResolvedValueOnce({
            data: { posters: [{ id: 1, media_type: 'movie', mpaa_rating: 'PG', show: false }] },
        });
        await vi.advanceTimersByTimeAsync(5000);

        expect(store.mediaPosters.filter((p) => p.show)).toHaveLength(1);
    });

    it('does not leave a second settings poll running when started twice', () => {
        const store = usePostersStore();
        store.getSettings = vi.fn();

        store.startSettingsInterval();
        store.startSettingsInterval();
        vi.advanceTimersByTime(store.settingsIntervalTime);

        expect(store.getSettings).toHaveBeenCalledTimes(1);
    });
});
