import { readFileSync } from 'node:fs';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('socket.io-client', () => ({ io: vi.fn(() => ({ on: vi.fn(), disconnect: vi.fn() })) }));

import { usePostersStore } from '@/store/posters';
import Dashboard from '@/Views/Dashboard.vue';

/**
 * Regression: the cross-fade works by putting the incoming poster over the
 * outgoing one. That was driven by a z-index on the element the transition
 * classes land on - but every poster sits in its own wrapper, and those
 * wrappers are their own stacking contexts, so the inner z-index could only
 * order an element against siblings it did not have. Across wrappers the order
 * was document order, so going from the last poster back to the first the new
 * one arrived underneath and the cross-fade came out as a hard cut.
 *
 * The ordering lives on the wrappers now, which is a DOM change rather than a
 * computed style, so it can be checked here.
 */
function dashboard(transitionType) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const store = usePostersStore();
    store.settings = { transition_type: transitionType, poster_display_speed: 15000 };
    store.loading = false;

    return mount(Dashboard, {
        global: {
            plugins: [pinia],
            stubs: { TopHeader: true, BottomFooter: true, TheaterName: true, VotingScreen: true },
        },
    });
}

/** An element inside a poster wrapper, as the transition hooks receive it. */
function posterElement() {
    const wrapper = document.createElement('div');
    wrapper.className = 'poster';
    const inner = document.createElement('div');
    wrapper.appendChild(inner);
    document.body.appendChild(wrapper);

    return inner;
}

describe('cross-fade stacking', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('lifts the arriving poster above the one it replaces', () => {
        const vm = dashboard('crossfade').vm;
        const arriving = posterElement();
        const leaving = posterElement();

        vm.liftPoster(arriving);
        vm.sinkPoster(leaving);

        expect(Number(arriving.parentElement.style.zIndex)).toBeGreaterThan(
            Number(leaving.parentElement.style.zIndex)
        );
    });

    it('orders the wrapper, since the inner element cannot order across wrappers', () => {
        const vm = dashboard('crossfade').vm;
        const arriving = posterElement();

        vm.liftPoster(arriving);

        expect(arriving.parentElement.style.zIndex).toBe('2');
        expect(arriving.style.zIndex).toBe('');
    });

    it('clears only the poster it is handed, not every wrapper', () => {
        // A late leave from the previous change used to clear the lift the
        // current one had just set, dropping the arriving poster underneath.
        const vm = dashboard('crossfade').vm;
        const arriving = posterElement();
        const leaving = posterElement();

        vm.liftPoster(arriving);
        vm.sinkPoster(leaving);
        vm.resetPosterLayer(leaving);

        expect(leaving.parentElement.style.zIndex).toBe('');
        expect(arriving.parentElement.style.zIndex).toBe('2');
    });

    it('clears the wrapper even once the poster has left the tree', () => {
        // after-leave runs after removal, so the element has no parent by then.
        const vm = dashboard('crossfade').vm;
        const leaving = posterElement();
        const wrapper = leaving.parentElement;

        vm.sinkPoster(leaving);
        expect(wrapper.style.zIndex).toBe('1');

        leaving.remove();
        vm.resetPosterLayer(leaving);

        expect(wrapper.style.zIndex).toBe('');
    });

    it('keeps the header, theatre name and footer above the poster in fill mode', () => {
        // The cross-fade puts 1 and 2 on the poster wrappers, and the entering
        // one keeps its 2 until the outgoing poster has gone. At 2 the chrome
        // tied with it and lost on document order, so the header, the theatre
        // name and the footer vanished under the artwork and stayed there.
        const source = readFileSync('resources/js/Views/Dashboard.vue', 'utf8');

        const layerUsedByLift = Math.max(
            ...[...source.matchAll(/setPosterLayer\(el, '(\d+)'\)/g)].map((m) => Number(m[1]))
        );

        const fillBlock = source.slice(source.indexOf('.fill-screen {'));
        const chrome = fillBlock.match(
            /\.top-header,\s*\.poster-footer,\s*\.theater-name \{[\s\S]*?z-index:\s*(\d+)/
        );

        expect(layerUsedByLift).toBeGreaterThan(0);
        expect(chrome).not.toBeNull();
        expect(Number(chrome[1])).toBeGreaterThan(layerUsedByLift);
    });

    it.each(['fade', 'vertical', 'cut'])('leaves %s alone', (type) => {
        const vm = dashboard(type).vm;
        const arriving = posterElement();

        vm.liftPoster(arriving);

        expect(arriving.parentElement.style.zIndex).toBe('');
    });
});
