<template>
    <header
        class="top-header"
        :class="{ 'top-header--spread': plateSpreads }"
        :style="'background-color:' + settings.header_bg_color"
    >
        <span
            v-if="showHeaderText"
            ref="plate"
            class="dmp-plate"
            :class="plateClasses"
            :style="plateVars"
        >
            <h1 ref="plateText" class="dmp-plate-text" :style="headerSize + headerFont">
                {{ headerText }}
            </h1>
        </span>
    </header>
</template>
<script>
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import spreadsText from '@/mixins/spreads-text';

export default {
    data: function () {
        return {};
    },
    mixins: [spreadsText],
    computed: {
        ...mapState(usePostersStore, ['settings', 'nowPlaying']),
        /**
         * The settings API hands booleans back as 0 and 1, so this cannot just
         * test against false - 0 !== false, and the header stayed on. Absent is
         * still on: an install that predates this option should not have its
         * header disappear.
         */
        showHeaderText() {
            const value = this.settings.show_header_text;

            return value === undefined || value === null ? true : !!Number(value);
        },
        plateStyleName() {
            const styles = ['plain', 'rules', 'marquee', 'plaque', 'neon'];

            return styles.includes(this.settings.header_style)
                ? this.settings.header_style
                : 'plain';
        },
        plateClasses() {
            return [
                'dmp-plate--' + this.plateStyleName,
                { 'dmp-plate--full': !!this.settings.header_full_width },
            ];
        },
        /**
         * Rules already fill the width - the hairlines grow into whatever the
         * words leave behind, so spreading the words would leave them nothing
         * to draw. Every other style spans something of its own and looks
         * lopsided with a short line of type marooned in the middle of it.
         */
        plateSpreads() {
            return !!this.settings.header_full_width && this.plateStyleName !== 'rules';
        },
        spreadKey() {
            return [
                this.showHeaderText,
                this.headerText,
                this.settings.header_font,
                this.settings.header_font_size,
                this.plateStyleName,
            ].join('|');
        },
        /**
         * The decorations draw themselves in currentColor. A plaque keeps its
         * own border colour, because that was a separate setting before this
         * became a style and displays using it should not lose it.
         */
        plateVars() {
            const vars = { color: this.settings.header_text_color };

            if (this.settings.header_style === 'plaque' && this.settings.header_border_color) {
                vars.borderColor = this.settings.header_border_color;
            }

            return vars;
        },
        headerFont() {
            if (this.settings.header_font === 'default') {
                return '';
            }
            if (this.settings.header_font === 'riemann-theater') {
                return "font-family: 'Riemann Theatre'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'great-attraction') {
                return "font-family: 'Great Attraction'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'midnight-champion') {
                return "font-family: 'Midnight Champion'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'emerald') {
                return "font-family: 'Emerald Grey'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'airstrike') {
                return "font-family: 'Airstrike'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'space-ranger') {
                return "font-family: 'Space Ranger'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'feast-flesh') {
                return "font-family: 'Feast of Flesh BB'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'camp-blood') {
                return "font-family: 'CSNPWDT NFI'; font-weight: normal; ";
            }
            if (this.settings.header_font === 'friday13') {
                return "font-family: 'Friday13'; font-weight: normal; ";
            }
        },
        headerSize() {
            if (this.settings.header_font_size === 'normal') {
                return '';
            }
            if (this.settings.header_font_size === 'xsmall') {
                return 'font-size: 3.5vh; ';
            }
            if (this.settings.header_font_size === 'small') {
                return 'font-size: 5vh; ';
            }
            if (this.settings.header_font_size === 'large') {
                return 'font-size: 7.5vh; padding: 8px 20px 10px 20px; ';
            }
            if (this.settings.header_font_size === 'xlarge') {
                return 'font-size: 9vh; padding: 6px 18px 8px 18px; ';
            }
        },
        headerText() {
            return this.nowPlaying
                ? this.settings.now_playing_text
                : this.settings.coming_soon_text;
        },
    },
};
</script>
<style lang="scss" scoped>
.top-header {
    width: 100%;
    display: flex;
    min-height: 14vh;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;

    h1 {
        text-transform: uppercase;
        font-size: 4vw;
        font-weight: 700;
        line-height: 1;
        letter-spacing: 3px;
        margin: 0;
    }
}

/*
 * A gutter, so a plate spanning the width has a little air at each end rather
 * than running into the bezel. It matters most for a plaque, whose border would
 * otherwise sit on the very edge of the screen and read as cut off.
 */
.top-header--spread {
    padding-left: 2vw;
    padding-right: 2vw;
}

.rotated {
    .top-header {
        min-height: 13.5vh;
        h1 {
            font-size: 6vh;
        }
    }
}
</style>
