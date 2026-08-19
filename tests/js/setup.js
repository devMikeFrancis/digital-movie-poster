/**
 * jsdom parses and styles but does not lay out, so it has no ResizeObserver.
 * Components that keep text fitted to its box observe their own size, and
 * without this they cannot be mounted at all.
 *
 * The stub does nothing on purpose: a test environment with no layout has no
 * size changes to report, and everything that depends on measurement is tested
 * against the measuring functions directly rather than through the DOM.
 */
if (!globalThis.ResizeObserver) {
    globalThis.ResizeObserver = class {
        observe() {}
        unobserve() {}
        disconnect() {}
    };
}
