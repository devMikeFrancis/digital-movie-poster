import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), emit: vi.fn() })) }));

import { usePostersStore } from '@/store/posters';

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

    it('does nothing when there is no socket to ask', () => {
        const store = usePostersStore();
        store.socket = '';

        expect(() => store.requestDisplayReload()).not.toThrow();
    });
});
