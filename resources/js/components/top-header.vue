<template>
    <header
        class="top-header"
        :class="{
            'reserve-runtime': plateSpreads && settings.show_runtime,
            'reserve-speaker': plateSpreads && speakerInHeader,
        }"
        :style="'background-color:' + settings.header_bg_color"
    >
        <Transition :name="transitionName" mode="out-in">
            <span
                class="runtime"
                v-if="settings.show_runtime && localRuntime"
                :key="posterKey"
                :style="'color:' + settings.header_text_color"
                >{{ localRuntime }} min</span
            >
        </Transition>
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
        <SpeakerConfig v-if="speakerInHeader" />
    </header>
</template>
<script>
import { mapGetters, mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import SpeakerConfig from '@/components/speaker-config.vue';
import spreadsText from '@/mixins/spreads-text';

export default {
    data: function () {
        return {};
    },
    mixins: [spreadsText],
    components: {
        SpeakerConfig,
    },
    computed: {
        ...mapState(usePostersStore, [
            'settings',
            'nowPlaying',
            'runtime',
            'nowPlayingRuntime',
            'currentPosterId',
        ]),
        ...mapGetters(usePostersStore, ['transitionPrefix']),
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
        transitionName() {
            return this.transitionPrefix + '-meta';
        },
        speakerInHeader() {
            return (
                this.settings.speaker_config_location === 'top-right' &&
                !!this.settings.show_speaker_config
            );
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
                // These two decide how much room the line is left with.
                this.settings.show_runtime,
                this.speakerInHeader,
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
        posterKey() {
            return this.nowPlaying ? 'now-playing' : this.currentPosterId;
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
        localRuntime() {
            let rt = this.nowPlaying ? this.nowPlayingRuntime : this.runtime;
            return rt ? rt.toFixed(0) : false;
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
 * The runtime belongs to the poster, so it changes with it rather than
 * snapping to the next film while the artwork is still fading.
 */
/*
 * The details swap rather than overlap. The poster's cross-fade holds the
 * outgoing image at full opacity while the incoming one covers it, which works
 * because a poster covers a poster. These are centred text and icons of
 * different widths, so nothing covers anything and both sets were readable at
 * once. With out-in below, the old details leave before the new ones arrive,
 * and the whole swap still fits inside the poster's change.
 */
.fade-meta-enter-active,
.fade-meta-leave-active,
.crossfade-meta-enter-active,
.crossfade-meta-leave-active {
    transition: opacity 0.6s ease;
}

.fade-meta-enter-from,
.fade-meta-leave-to,
.crossfade-meta-enter-from,
.crossfade-meta-leave-to {
    opacity: 0;
}

.slide-meta-enter-active,
.slide-meta-leave-active {
    transition:
        transform 0.5s ease,
        opacity 0.5s ease;
}

.slide-meta-leave-to {
    transform: translate3d(0, -60%, 0);
    opacity: 0;
}

.slide-meta-enter-from {
    transform: translate3d(0, 60%, 0);
    opacity: 0;
}

.cut-meta-enter-active,
.cut-meta-leave-active {
    transition: none;
}

/*
 * The runtime and the speaker badge float over the header rather than sitting
 * in the row with it, so a line of text spread to the full width runs straight
 * underneath them. These keep it clear of the space they take.
 *
 * A fixed reserve rather than a measurement of the badges: the runtime changes
 * with every poster, and reserving its exact width would shift the header text
 * a little on every change. The numbers are the offset each badge is positioned
 * at plus room for its longest sensible contents at its own type size.
 */
.top-header.reserve-runtime {
    padding-left: 16vw;
}

.top-header.reserve-speaker {
    padding-right: 13vw;
}

.runtime {
    position: absolute;
    top: 50%;
    left: 2%;
    color: #fff;
    font-size: 3vw;
    font-weight: 400;
    transform: translateY(-50%);
}

.rotated {
    .top-header {
        min-height: 13.5vh;
        h1 {
            font-size: 6vh;
        }
    }
    .runtime {
        font-size: 1.4vw;
        left: 5%;
    }
    // Smaller type here, so the reserve above is more than it needs.
    .top-header.reserve-runtime {
        padding-left: 11vw;
    }
}
</style>
