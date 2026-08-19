<template>
    <header class="top-header" :style="'background-color:' + settings.header_bg_color">
        <Transition :name="transitionName">
            <span
                class="runtime"
                v-if="settings.show_runtime && localRuntime"
                :key="posterKey"
                :style="'color:' + settings.header_text_color"
                >{{ localRuntime }} min</span
            >
        </Transition>
        <h1
            v-if="showHeaderText"
            :style="
                headerSize +
                headerFont +
                'color:' +
                settings.header_text_color +
                '; border-color: ' +
                settings.header_border_color +
                ';' +
                (settings.show_header_border
                    ? 'border-size: 1px; border-type: solid'
                    : 'border: none; padding: 0')
            "
        >
            {{ headerText }}
        </h1>
        <SpeakerConfig
            v-if="settings.speaker_config_location === 'top-right' && settings.show_speaker_config"
        />
    </header>
</template>
<script>
import { mapGetters, mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import SpeakerConfig from '@/components/speaker-config.vue';

export default {
    data: function () {
        return {};
    },
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
        padding: 12px 24px 14px 24px;
        border: 4px solid #fff;
        font-size: 4vw;
        font-weight: 700;
        color: #fff;
        line-height: 1;
        letter-spacing: 3px;
        margin: 0;
    }
}

/*
 * The runtime belongs to the poster, so it changes with it rather than
 * snapping to the next film while the artwork is still fading.
 */
.fade-meta-enter-active,
.fade-meta-leave-active {
    transition: opacity 2.2s ease;
}

.fade-meta-enter-from,
.fade-meta-leave-to {
    opacity: 0;
}

.slide-meta-enter-active,
.slide-meta-leave-active {
    transition:
        transform 1.2s ease,
        opacity 1s ease;
}

.slide-meta-leave-to {
    transform: translate3d(0, -100%, 0);
    opacity: 0;
}

.slide-meta-enter-from {
    transform: translate3d(0, 100%, 0);
    opacity: 0;
}
.crossfade-meta-enter-active {
    transition: opacity 1.6s ease;
    z-index: 2;
}

.crossfade-meta-enter-from {
    opacity: 0;
}

.crossfade-meta-leave-active {
    transition: opacity 0.01s linear 1.6s;
    z-index: 1;
}

.crossfade-meta-leave-to {
    opacity: 0;
}

.cut-meta-enter-active,
.cut-meta-leave-active {
    transition: none;
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
}
</style>
