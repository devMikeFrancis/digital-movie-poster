<template>
    <div>
        <VotingScreen @active="onVotingActive" />

        <div class="movie-posters">
            <div class="loading-overlay" v-if="loading">
                <div class="p-6" @click="gotoPosters()" v-html="loadingMessage"></div>
            </div>
            <div
                id="recent-added-container"
                class="poster-container"
                :class="[{ 'fill-screen': fillScreen }, scrimClass]"
                @click.prevent="gotoPosters()"
                v-if="!isPlaying"
            >
                <TheaterName v-if="theaterNameOn === 'top'" />
                <TopHeader />
                <div class="recent-poster-container">
                    <div class="trailer-container has-trailer">
                        <div
                            class="poster-items"
                            :style="'background-color: ' + settings.poster_bg_color"
                        >
                            <div
                                v-for="(poster, index) in mediaPosters"
                                v-bind:key="`key-${index}`"
                                class="poster"
                                :class="{
                                    'has-trailer': poster.show_trailer && poster.trailer_path,
                                }"
                                :style="blackBars(poster)"
                            >
                                <Transition
                                    :name="transitionPrefix + '-poster'"
                                    @enter="liftPoster"
                                    @before-leave="sinkPoster"
                                    @after-leave="resetPosterLayer"
                                    @leave-cancelled="resetPosterLayer"
                                >
                                    <div
                                        v-if="poster.show"
                                        :style="
                                            'background-image: url(storage/posters/' +
                                            poster.file_name +
                                            ')'
                                        "
                                    ></div>
                                </Transition>
                            </div>
                        </div>

                        <div id="trailer">
                            <div ref="videoPlayer" id="youtube-player"></div>
                        </div>
                        <div id="music"></div>
                    </div>
                </div>
                <BottomFooter />
                <TheaterName v-if="theaterNameOn === 'bottom'" />
            </div>
            <transition name="fade" mode="out-in">
                <div
                    id="now-playing-container"
                    class="poster-container"
                    :class="[{ 'fill-screen': fillScreen }, scrimClass]"
                    v-if="isPlaying"
                    @click.prevent="gotoPosters()"
                >
                    <TheaterName v-if="theaterNameOn === 'top'" />
                    <TopHeader />

                    <div class="now-playing-container">
                        <div
                            class="now-playing-poster"
                            :style="
                                'background-image: url(' +
                                nowPlayingPoster +
                                ');' +
                                blackBars(false)
                            "
                        ></div>
                    </div>

                    <BottomFooter />
                    <TheaterName v-if="theaterNameOn === 'bottom'" />
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
import TopHeader from '@/components/top-header.vue';
import VotingScreen from '@/components/voting-screen.vue';
import BottomFooter from '@/components/bottom-footer.vue';
import TheaterName from '@/components/theater-name.vue';

const $recentAdded = document.querySelector('#recent-added-container');
let $video = document.getElementById('youtube-player');

export default {
    data: function () {
        return {
            borderWidth: 2,
            starSize: 28,
            votingActive: false,
        };
    },
    components: {
        TopHeader,
        VotingScreen,
        BottomFooter,
        TheaterName,
    },
    watch: {
        nowPlaying: {
            handler: function (val) {
                if (val) {
                    this.getNowPlaying();
                } else {
                    this.setVideoPlayerRef(this.$refs.videoPlayer);
                    this.setIsPlaying(false);
                }
            },
            deep: true,
        },
        isPlaying: {
            handler: function (val) {
                if (val) {
                    this.stopMusic();
                    this.getNowPlaying();
                    this.stopTransitionImages();
                } else {
                    if (!this.loading && !this.votingActive) {
                        console.log('WATCHER - nowPlaying START TRANSITION IMAGES');
                        this.startTransitionImages();
                    }
                }
            },
            deep: true,
        },
    },
    computed: {
        ...mapState(usePostersStore, [
            'loading',
            'loadingMessage',
            'settings',
            'moviePosters',
            'nowPlaying',
            'isPlaying',
            'nowPlayingPoster',
            'theme_music',
            'socket',
        ]),
        ...mapGetters(usePostersStore, ['mediaPosters', 'transitionPrefix']),
        fillScreen() {
            return !!this.settings.poster_fill_screen;
        },
        scrimClass() {
            const choice = this.settings.poster_fill_scrim || 'standard';

            return this.fillScreen ? 'scrim-' + choice : '';
        },
        theaterNameOn() {
            if (!this.settings.show_theater_name || !this.settings.theater_name) {
                return false;
            }

            return this.settings.theater_name_position === 'top' ? 'top' : 'bottom';
        },
    },
    methods: {
        ...mapActions(usePostersStore, [
            'boot',
            'stopTransitionImages',
            'startTransitionImages',
            'setLoading',
            'setSocket',
            'getNowPlaying',
            'setIsPlaying',
            'setNowPlayingPoster',
            'setVideoPlayerRef',
            'playMusic',
            'stopMusic',
            'playTrailer',
            'playVideo',
        ]),
        /**
         * A vote takes the display over, so the slideshow is parked while one
         * is on - the same treatment now-playing gets. Without this the posters
         * carry on cycling behind the voting screen, and the trailer audio
         * carries on over it.
         */
        onVotingActive(active) {
            this.votingActive = active;

            if (active) {
                this.stopTransitionImages();
                this.stopMusic();

                return;
            }

            if (!this.loading && !this.isPlaying) {
                this.startTransitionImages();
            }
        },
        /*
         * Stacking for the cross-fade, applied to each poster's wrapper rather
         * than to the element the transition classes land on.
         *
         * Every poster in the list has its own wrapper, and those wrappers are
         * fixed with will-change, so each is its own stacking context - a
         * z-index on the inner element can only order it against its own
         * siblings, of which it has none. Across wrappers the order was simply
         * document order, so whether the incoming poster arrived on top of the
         * outgoing one depended on where the two happened to sit in the list.
         * Going from the last poster back to the first, it arrived underneath,
         * and the cross-fade came out as a hard cut.
         */
        /*
         * On enter rather than before-enter: Vue runs before-enter while the
         * element is still detached, so there is no wrapper to put a z-index on
         * yet and the lift quietly did nothing. Leaving worked, which made it
         * look as though only half the mechanism was wired up.
         */
        liftPoster(el) {
            this.setPosterLayer(el, '2');
        },
        sinkPoster(el) {
            // after-leave runs once the element is out of the tree, so note
            // where it was while it is still possible to.
            if (el) {
                el.__posterWrapper = el.parentElement;
            }

            this.setPosterLayer(el, '1');
        },
        setPosterLayer(el, value) {
            if (this.transitionPrefix !== 'crossfade') {
                return;
            }

            if (el && el.parentElement) {
                el.parentElement.style.zIndex = value;
            }
        },
        /**
         * Only ever clears the wrapper it is handed. Clearing all of them
         * looked tidier and was wrong: a late leave from the previous change
         * wiped the lift the current one had just set, dropping the arriving
         * poster back underneath.
         */
        resetPosterLayer(el) {
            const wrapper = el && (el.parentElement || el.__posterWrapper);

            // Not guarded on the transition type: clearing an inline z-index is
            // always safe, and the type may have been changed mid-change.
            if (wrapper) {
                wrapper.style.zIndex = '';
            }
        },
        blackBars(poster) {
            if (poster) {
                if (poster.show_trailer && poster.trailer_path) {
                    return '';
                }
            }

            // Edge to edge is the whole point of fill mode, and this inline
            // style would otherwise win over the stylesheet and leave the
            // artwork inset by 1.5vw and overflowing on the other side.
            if (this.fillScreen) {
                return '';
            }

            return this.settings.remove_black_bars
                ? ' left: 0; right: 0; '
                : ' left: 1.5vw; right: 1.5vw; ';
        },
        gotoSettings() {
            this.$router.push('settings');
        },
        gotoPosters() {
            this.$router.push('posters');
        },
    },
    created() {
        this.boot();
    },
    mounted() {
        this.setLoading(true);
        // TODO FIXME This breaks if the app is booted with nowplaying is true
        this.setVideoPlayerRef(this.$refs.videoPlayer);
        if (typeof io !== 'undefined') {
            this.setSocket();
        }
    },
};
</script>

<style lang="scss">
.poster-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.movie-posters {
    background-color: #000;
    color: #fff;
    overflow: auto;
}

.rotated {
    .movie-posters {
        height: 100vw;
        width: 100vh;
        transform: rotate(90deg);
        transform-origin: bottom left;

        position: absolute;
        top: -100vw;
        left: 0;
    }
}

#posters-container {
    z-index: 2;
}

#now-playing-container {
    z-index: 3;
}

.recent-poster-container,
.now-playing-container {
    width: 100%;
    flex-grow: 2;
    position: relative;
    overflow: hidden;
}

.trailer-container {
    position: relative;
    width: 100%;
    height: 100%;

    &.has-trailer {
        background: radial-gradient(
            circle,
            rgba(255, 255, 255, 0.3) 0%,
            rgba(000, 000, 000, 1) 60%
        );
    }
}

#trailer {
    height: 48vw;
    width: 100%;
    position: absolute;
    left: 0;
    bottom: 0;
    z-index: 4;
}

.rotated {
    #trailer {
        height: 28vw;
        width: 100%;
    }
}

/*
 * Fill mode. The poster is normally boxed between the header and the footer;
 * here it takes the whole screen and everything else floats over it, which is
 * what a display with nothing but artwork on it wants.
 *
 * Scaled to fit rather than cropped: a 2:3 poster on a 16:9 screen comes out
 * full height, which is the point, and nothing is ever cut off - cropping to
 * cover loses the top and bottom of the artwork, which on a poster is usually
 * the title and the billing block. The trailer keeps its own box; a video
 * stretched to an arbitrary screen ratio looks wrong in a way a poster does
 * not.
 */
.fill-screen {
    /*
     * The artwork is lifted out to cover the whole screen while the header,
     * theatre name and footer stay in their normal places on top of it. Taking
     * the poster's container out of the flow instead looked obvious and was
     * wrong: with nothing left to grow, the header and footer collapsed into a
     * stack in the middle of the screen.
     */
    .poster:not(.has-trailer) {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        transform: none;

        > div {
            aspect-ratio: auto;
            width: 100%;
            height: 100%;
            background-size: contain;
            background-position: center;
        }
    }

    .now-playing-poster {
        position: fixed;
        inset: 0;
        background-size: contain;
        background-position: center;
    }

    /*
     * The chrome floats over the artwork rather than sitting on it. With its
     * configured background colour it was an opaque bar at each end, hiding
     * about a third of a full-height poster - the artwork was never cropped,
     * it was covered up. A gradient behind the two ends keeps the text
     * readable over whatever happens to be there.
     */
    .top-header,
    .poster-footer,
    .theater-name {
        position: relative;
        z-index: 2;
        background-color: transparent !important;
    }

    &::before,
    &::after {
        content: '';
        position: fixed;
        left: 0;
        right: 0;
        height: var(--scrim-height, 30vh);
        z-index: 1;
        pointer-events: none;
    }

    &::before {
        top: 0;
        background: linear-gradient(
            to bottom,
            rgb(0 0 0 / var(--scrim-alpha, 0.85)),
            rgb(0 0 0 / 0)
        );
    }

    &::after {
        bottom: 0;
        background: linear-gradient(to top, rgb(0 0 0 / var(--scrim-alpha, 0.85)), rgb(0 0 0 / 0));
    }
}

/*
 * How heavily the ends are shaded. A dark poster needs none of this; a bright
 * one needs quite a lot before white text on it is readable, and that is a
 * judgement about a particular room and screen rather than something worth
 * guessing at.
 */
.scrim-none::before,
.scrim-none::after {
    display: none;
}

.scrim-subtle {
    --scrim-height: 20vh;
    --scrim-alpha: 0.6;
}

.scrim-standard {
    --scrim-height: 30vh;
    --scrim-alpha: 0.85;
}

.scrim-strong {
    --scrim-height: 42vh;
    --scrim-alpha: 0.95;
}

.poster {
    height: 100%;
    flex-grow: 2;
    display: flex;
    justify-content: center;
    position: absolute;
    top: 0;
    backface-visibility: hidden;
    will-change: opacity;

    > div {
        aspect-ratio: 2/3;
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
        backface-visibility: hidden;
        will-change: opacity;
    }

    &.has-trailer {
        width: auto;
        height: 42vh;
        aspect-ratio: 2/3;
        left: 50%;
        transform: translate3d(-50%, 0, 0);
    }
}

.rotated {
    .poster {
        &.has-trailer {
            width: 35vw;
            height: 52vw;
            left: 50%;
            transform: translate3d(-50%, 0, 0);
        }
    }
}

.poster-items {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.now-playing-poster {
    height: 100%;
    flex-grow: 2;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    position: absolute;
    top: 0;
}

.fade-poster-enter-active,
.fade-poster-leave-active {
    transition: opacity 2.2s ease;
}

.fade-poster-enter-from,
.fade-poster-leave-to {
    opacity: 0;
}

.slide-poster-enter-active,
.slide-poster-leave-active {
    transition:
        transform 1.2s ease,
        opacity 1s ease;
    transform: translate3d(0, 0, 0);
}

.slide-poster-leave-to {
    transform: translate3d(0, -100%, 0);
    opacity: 0;
}
.slide-poster-enter-from {
    transform: translate3d(0, 100%, 0);
}

/*
 * Cross-fade. Fade runs both halves at once, so mid-change the pair is briefly
 * half transparent and the background shows through as a dip. Here the
 * incoming poster fades in on top while the outgoing one holds at full
 * opacity underneath and is only dropped once it is covered, so the screen
 * never darkens between posters.
 */
/*
 * Ordering is handled on the wrappers in JS - see liftPoster - because each
 * poster sits in its own stacking context and cannot be ordered against the
 * others from here.
 */
.crossfade-poster-enter-active {
    transition: opacity 1.6s ease;
}

.crossfade-poster-enter-from {
    opacity: 0;
}

.crossfade-poster-leave-active {
    transition: opacity 0.01s linear 1.6s;
}

.crossfade-poster-leave-to {
    opacity: 0;
}

/* Cut. No animation at all - one poster replaces the next outright. */
.cut-poster-enter-active,
.cut-poster-leave-active {
    transition: none;
}
</style>
