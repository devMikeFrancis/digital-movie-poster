import { spreadToWidth, clearSpread } from '@/support/spread-text';

/**
 * Keeps a plate's text spread to the plate's width.
 *
 * The measuring is the easy half; knowing when to measure again is the rest of
 * it. The line has to be re-spread when the screen changes size, when the
 * wording changes, and when the typeface finally arrives - a display boots
 * with the fallback face in place, and a line measured against that one is the
 * wrong width the moment the real one loads.
 *
 * The component supplies:
 *   - ref="plate" on the plate, ref="plateText" on the words inside it
 *   - plateSpreads, whether this plate should be spreading at all
 *   - spreadKey, anything else that changes the width of the line
 */
export default {
    computed: {
        /** Re-measure whenever any of this moves. */
        spreadWatch() {
            return [this.plateSpreads, this.spreadKey].join('|');
        },
    },
    watch: {
        spreadWatch() {
            this.scheduleSpread();
        },
    },
    mounted() {
        // The root rather than the plate: the plate can be behind a v-if and
        // not exist yet, and it is the width of the root that decides the
        // width of the plate anyway.
        this.spreadObserver = new ResizeObserver(() => this.applySpread());
        this.spreadObserver.observe(this.$el);

        // Belt and braces. The display itself never changes size, but a
        // browser window previewing it does, and an observer is not delivered
        // in every environment this runs in.
        this.spreadOnResize = () => this.applySpread();
        window.addEventListener('resize', this.spreadOnResize);

        this.scheduleSpread();

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => this.applySpread());
        }
    },
    beforeUnmount() {
        if (this.spreadObserver) {
            this.spreadObserver.disconnect();
            this.spreadObserver = null;
        }

        window.removeEventListener('resize', this.spreadOnResize);
    },
    methods: {
        /**
         * Wait for the render that changed the wording before measuring it,
         * otherwise the old line gets measured and the new one wears the
         * answer.
         */
        scheduleSpread() {
            this.$nextTick(() => this.applySpread());
        },
        applySpread() {
            const text = this.$refs.plateText;
            const plate = this.$refs.plate;

            if (!text || !plate) {
                return;
            }

            if (!this.plateSpreads) {
                clearSpread(text);

                return;
            }

            spreadToWidth(text, plate);
        },
    },
};
