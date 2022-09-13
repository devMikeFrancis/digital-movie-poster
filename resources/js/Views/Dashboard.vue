<template>
    <div>
        <div class="movie-posters">
            <div class="loading-overlay" v-if="loading">
                <div class="p-12" @click="gotoPosters()">{{ loadingMessage }}</div>
            </div>
            <div
                id="recent-added-container"
                class="poster-container"
                @click.prevent="gotoPosters()"
                v-if="!nowPlaying"
            >
                <header
                    class="coming-soon-header"
                    :style="'background-color:' + settings.header_bg_color"
                >
                    <span
                        class="runtime"
                        v-if="settings.show_runtime && runtime"
                        :style="'color:' + settings.header_text_color"
                        >{{ runtime.toFixed(0) }} min</span
                    >
                    <h1
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
                        {{ settings.coming_soon_text }}
                    </h1>
                </header>
                <div class="recent-poster-container">
                    <div class="trailer-container has-trailer">
                        <div
                            class="poster-items"
                            :style="'background-color: ' + settings.poster_bg_color"
                        >
                            <div
                                v-for="(poster, index) in moviePosters"
                                v-bind:key="`key-${index}`"
                                class="poster"
                                :class="{
                                    'has-trailer': poster.show_trailer && poster.trailer_path,
                                }"
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
                <footer
                    class="coming-soon-footer"
                    :style="'background-color:' + settings.footer_bg_color"
                >
                    <ContentRating />
                    <ProcessingLogos />

                    <div class="audience-rating" v-if="settings.show_audience_rating">
                        <star-rating
                            :increment="0.1"
                            :max-rating="5"
                            :inactive-color="'#000'"
                            :active-color="settings.footer_text_color"
                            :star-size="starSize"
                            :rating="audienceRating"
                            :border-color="settings.footer_text_color"
                            :border-width="borderWidth"
                            :show-rating="false"
                            :read-only="true"
                        />
                    </div>
                </footer>
            </div>
            <transition name="fade" mode="out-in">
                <div
                    id="now-playing-container"
                    class="poster-container"
                    v-if="nowPlaying"
                    @click.prevent="gotoPosters()"
                >
                    <header
                        class="now-playing-header"
                        :style="'background-color:' + settings.header_bg_color"
                    >
                        <span
                            class="runtime"
                            v-if="settings.show_runtime && nowPlayingRuntime"
                            :style="'color:' + settings.header_text_color"
                            >{{ nowPlayingRuntime.toFixed(0) }} min</span
                        >
                        <h1
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
                            {{ settings.now_playing_text }}
                        </h1>
                    </header>
                    <div class="now-playing-container">
                        <div
                            class="now-playing-poster"
                            :style="'background-image: url(' + nowPlayingPoster + ')'"
                        ></div>
                    </div>
                    <div
                        class="now-playing-footer"
                        :style="'background-color:' + settings.footer_bg_color"
                    >
                        <ContentRating />
                        <ProcessingLogos />

                        <div class="audience-rating" v-if="settings.show_audience_rating">
                            <star-rating
                                :increment="0.1"
                                :max-rating="5"
                                inactive-color="#000"
                                :active-color="settings.footer_text_color"
                                :star-size="starSize"
                                v-model:rating="rating"
                                :border-color="settings.footer_text_color"
                                :border-width="borderWidth"
                                :show-rating="false"
                                :read-only="true"
                            />
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
import StarRating from 'vue-star-rating';
import { mapState, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
import ProcessingLogos from '@/components/processing-logos.vue';
import ContentRating from '@/components/content-rating.vue';

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
        StarRating,
        ProcessingLogos,
        ContentRating,
    },
    watch: {
        nowPlaying: {
            handler: function (val) {
                if (val) {
                    this.stopMusic();
                    this.getNowPlaying();
                    this.stopTransitionImages();
                    this.controlTV('on');
                } else {
                    this.setVideoPlayerRef(this.$refs.videoPlayer);
                    this.startTransitionImages();
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
            'nowPlayingPoster',
            'rating',
            'audienceRating',
            'contentRating',
            'theme_music',
            'runtime',
            'nowPlayingRuntime',
            'mpaaRating',
            'show_imax',
            'show_auro_3d',
            'show_dolby_vision_vertical',
            'show_dolby_atmos_vertical',
            'show_dolby_51',
            'show_dts',
            'socket',
        ]),
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
            'setNowPlayingPoster',
            'setVideoPlayerRef',
            'playMusic',
            'stopMusic',
            'playTrailer',
            'playVideo',
        ]),
        setStarSizes() {
            const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
            if (vw > 2000) {
                this.starSize = 30;
            }
            if (vw > 3000) {
                this.starSize = 44;
                this.borderWidth = 4;
            }
            if (vw <= 2000) {
                this.starSize = 24;
            }
        },
        gotoSettings() {
            this.$router.push('settings');
        },
        gotoPosters() {
            this.$router.push('posters');
        },
    },
    created() {
        this.setStarSizes();
        this.boot();
    },
    mounted() {
        this.setLoading(true);
        // TODO FIXME This breaks if the app is booted with nowplaying is true
        this.setVideoPlayerRef(this.$refs.videoPlayer);
        if (typeof io !== 'undefined') {
            this.setSocket();
        }
        window.addEventListener('resize', this.setStarSizes);
    },
};
</script>

<style scoped lang="scss">
body {
    overflow: hidden;

    .movie-posters {
        transform: rotate(90deg);
        transform-origin: bottom left;

        position: absolute;
        top: -100vw;
        left: 0;

        height: 100vw;
        width: 100vh;

        background-color: #000;
        color: #fff;

        overflow: auto;
    }
}

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

#recent-added-container {
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
    height: 28vw;
    width: 100%;
    position: absolute;
    left: 0;
    bottom: 0;
    z-index: 4;
}

.poster {
    height: 100%;
    flex-grow: 2;
    position: absolute;
    top: 0;
    left: 1.7vw;
    right: 1.7vw;
    backface-visibility: hidden;
    will-change: opacity;

    > div {
        width: 100%;
        height: 100%;
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
        backface-visibility: hidden;
        will-change: opacity;
    }

    &.has-trailer {
        width: 35vw;
        height: 50vw;
        left: 50%;
        transform: translate3d(-50%, 0, 0);
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
    left: 1.7vw;
    right: 1.7vw;
    flex-grow: 2;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    position: absolute;
    top: 0;
}

.now-playing-header,
.coming-soon-header {
    width: 100%;
    display: flex;
    min-height: 18vh;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;

    h1 {
        text-transform: uppercase;
        padding: 12px 24px 14px 24px;
        border: 4px solid #fff;
        font-size: 6.5vh;
        font-weight: 700;
        color: #fff;
        line-height: 1;
        letter-spacing: 3px;
        margin: 0;
    }
}

.now-playing-footer,
.coming-soon-footer {
    width: 100%;
    min-height: 18vh;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    padding: 4vh;
}

.runtime {
    position: absolute;
    top: 50%;
    left: 5%;
    color: #fff;
    font-size: 1.4vw;
    font-weight: 400;
    transform: translateY(-50%);
}

.audience-rating {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
    width: 14vh;
    margin-left: auto;
}

.vue-star-rating {
    margin-top: -4px;
}

.vue-star-rating-star {
    margin-right: 4px !important;
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
    transition: transform 1.2s ease;
    transform: translate3d(0, 0, 0);
}

.slide-poster-leave-to {
    transform: translate3d(0, -100%, 0);
}
.slide-poster-enter-from {
    transform: translate3d(0, 100%, 0);
}
</style>
