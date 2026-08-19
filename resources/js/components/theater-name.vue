<template>
    <div class="theater-name" :style="plateVars">
        <span ref="plate" class="dmp-plate" :class="plateClasses">
            <span ref="plateText" class="dmp-plate-text" :style="nameFont">{{
                settings.theater_name
            }}</span>
        </span>
    </div>
</template>

<script>
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import spreadsText from '@/mixins/spreads-text';

/**
 * The name of the room the display is in, above or below the poster.
 *
 * Borrows the header's font choice rather than adding another one to pick: on a
 * display where the header reads in a cinema face, a house name in the default
 * sans next to it looks like a mistake. The decoration is drawn in the header's
 * text colour for the same reason.
 */
export default {
    name: 'TheaterName',
    mixins: [spreadsText],
    computed: {
        ...mapState(usePostersStore, ['settings']),
        plateStyleName() {
            const styles = ['plain', 'rules', 'marquee', 'plaque', 'neon'];

            return styles.includes(this.settings.theater_name_style)
                ? this.settings.theater_name_style
                : 'plain';
        },
        plateClasses() {
            return [
                'dmp-plate--' + this.plateStyleName,
                { 'dmp-plate--full': !!this.settings.theater_name_full_width },
            ];
        },
        /**
         * Rules already fill the width - the hairlines grow into whatever the
         * words leave behind, so spreading the words would leave them nothing
         * to draw. Every other style spans something of its own and looks
         * lopsided with a short line of type marooned in the middle of it.
         */
        plateSpreads() {
            return !!this.settings.theater_name_full_width && this.plateStyleName !== 'rules';
        },
        spreadKey() {
            return [
                this.settings.theater_name,
                this.settings.header_font,
                this.plateStyleName,
            ].join('|');
        },
        /**
         * The decorations are drawn with currentColor, so they only need the
         * text colour set once here rather than threaded through each rule.
         */
        plateVars() {
            return {
                color: this.settings.header_text_color,
                backgroundColor: this.settings.footer_bg_color,
            };
        },
        nameFont() {
            const fonts = {
                'riemann-theater': 'Riemann Theatre',
                'great-attraction': 'Great Attraction',
                'midnight-champion': 'Midnight Champion',
                emerald: 'Emerald Grey',
                airstrike: 'Airstrike',
                'space-ranger': 'Space Ranger',
                'feast-flesh': 'Feast of Flesh BB',
                'camp-blood': 'CSNPWDT NFI',
                friday13: 'Friday13',
            };

            const font = fonts[this.settings.header_font];

            return font ? "font-family: '" + font + "'; font-weight: normal;" : '';
        },
    },
};
</script>

<style lang="scss" scoped>
.theater-name {
    width: 100%;
    padding: 1.4vh 2vw;
    text-align: center;
    line-height: 1.1;
}

.theater-name :deep(.dmp-plate-text) {
    font-size: 3.2vh;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    // The last letter's tracking would otherwise push the text off centre.
    text-indent: 0.14em;
}

.theater-name :deep(.dmp-plate--plaque .dmp-plate-text) {
    font-size: 2.9vh;
}

.rotated {
    .theater-name :deep(.dmp-plate-text) {
        font-size: 2.8vh;
    }
}
</style>
