<template>
    <div>
        <div class="movie-posters">
            <div class="loading-overlay" v-if="loading">
                <div class="p-6" @click="gotoPosters()" v-html="loadingMessage"></div>
            </div>
            <div
                id="recent-added-container"
                class="poster-container"
                @click.prevent="gotoPosters()"
                v-if="!isPlaying"
            >
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
                                    :name="
                                        settings.transition_type === 'fade'
                                            ? 'fade-poster'
                                            : 'slide-poster'
                                    "
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
            </div>
            <transition name="fade" mode="out-in">
                <div
                    id="now-playing-container"
                    class="poster-container"
                    v-if="isPlaying"
                    @click.prevent="gotoPosters()"
                >
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
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
import TopHeader from '@/components/top-header.vue';
import BottomFooter from '@/components/bottom-footer.vue';

const $recentAdded = document.querySelector('#recent-added-container');
let $video = document.getElementById('youtube-player');

export default {
    data: function () {
        return {
            borderWidth: 2,
            starSize: 28,
        };
    },
    components: {
        TopHeader,
        BottomFooter,
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
                    this.controlTV('on');
                } else {
                    if (!this.loading) {
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
        ...mapGetters(usePostersStore, ['mediaPosters']),
    },
    methods: {
        ...mapActions(usePostersStore, [
            'boot',
            'stopTransitionImages',
            'startTransitionImages',
            'controlTV',
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
        blackBars(poster) {
            if (poster) {
                if (poster.show_trailer && poster.trailer_path) {
                    return '';
                }
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
    transition: transform 1.2s ease, opacity 1s ease;
    transform: translate3d(0, 0, 0);
}

.slide-poster-leave-to {
    transform: translate3d(0, -100%, 0);
    opacity: 0;
}
.slide-poster-enter-from {
    transform: translate3d(0, 100%, 0);
}
</style>
