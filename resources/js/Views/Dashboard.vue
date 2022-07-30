<template>
    <div>
        <div class="movie-posters">
            <div class="loading-overlay" v-if="loading">
                <div class="p-12" @click="gotoPosters()">{{ loadingMessage }}</div>
            </div>
            <div id="recent-added-container" @click.prevent="gotoPosters()" v-if="!nowPlaying">
                <header class="coming-soon-header">
                    <span class="runtime" v-if="settings.show_runtime && runtime"
                        >{{ runtime.toFixed(0) }} min</span
                    >
                    <h1>{{ settings.coming_soon_text }}</h1>
                </header>
                <div class="recent-poster-container">
                    <div class="trailer-container has-trailer">
                        <div
                            class="poster-items"
                            :class="{
                                'vertical-items': settings.transition_type === 'vertical',
                                'fade-items': settings.transition_type === 'fade',
                            }"
                        >
                            <div
                                v-for="(poster, index) in moviePosters"
                                :class="{
                                    show: poster.show,
                                    hide: !poster.show,
                                    item: settings.transition_type === 'vertical',
                                    'recent-poster': settings.transition_type === 'fade',
                                    'next-item': poster.nextItem,
                                    'active-item': poster.activeItem,
                                    'past-item': poster.pastItem,
                                    'has-trailer': poster.show_trailer && poster.trailer_path,
                                }"
                                :style="
                                    'background-image: url(storage/posters/' +
                                    poster.file_name +
                                    ')'
                                "
                                v-bind:key="`key-${index}`"
                            ></div>
                        </div>

                        <div id="trailer">
                            <div ref="videoPlayer" id="youtube-player"></div>
                        </div>
                        <div id="music"></div>
                    </div>
                </div>
                <footer class="coming-soon-footer">
                    <div class="content-rating" v-if="settings.show_mpaa_rating">
                        <img
                            v-if="mpaaRating"
                            :src="'/images/' + mpaaRating + '.svg'"
                            alt="Content Rating"
                            v-cloak
                        />
                    </div>
                    <div class="dolby-logos" v-if="settings.show_processing_logos">
                        <div v-if="show_imax">
                            <img class="imax" src="/images/imax.png" alt="IMAX" />
                        </div>
                        <div v-if="show_auro_3d">
                            <img class="auro3d" src="/images/auro3d.svg" alt="Auro 3D" />
                        </div>
                        <div v-if="show_dolby_vision_vertical">
                            <img
                                src="/images/dolby-vision-stacked.svg"
                                alt="Dolby Vision"
                                class="dolby-vision-stacked"
                            />
                        </div>
                        <div v-if="show_dolby_atmos_vertical">
                            <img
                                src="/images/dolby-atmos-stacked.svg"
                                alt="Dolby Atmos"
                                class="dolby-atmos-stacked"
                            />
                        </div>
                        <div v-if="show_dolby_51">
                            <img
                                src="/images/dolby-51.svg"
                                alt="Dolby Digital 5.1"
                                class="dolby-atmos-stacked"
                            />
                        </div>
                        <div v-if="show_dts">
                            <img class="dts" src="/images/dts-x.svg" alt="DTS" />
                        </div>
                    </div>
                    <div class="audience-rating" v-if="settings.show_audience_rating">
                        <!--<star-rating
                            :increment="0.1"
                            :max-rating="5"
                            :inactive-color="'#000'"
                            :active-color="'#fff'"
                            :star-size="28"
                            :rating="audienceRating"
                            :border-color="'#fff'"
                            :border-width="borderWidth"
                            :show-rating="false"
                            :read-only="true"
                        />-->
                    </div>
                </footer>
            </div>
            <transition name="fade" mode="out-in">
                <div id="now-playing-container" v-if="nowPlaying" @click.prevent="gotoPosters()">
                    <header class="now-playing-header">
                        <span class="runtime" v-if="settings.show_runtime && nowPlayingRuntime"
                            >{{ nowPlayingRuntime.toFixed(0) }} min</span
                        >
                        <h1>{{ settings.now_playing_text }}</h1>
                    </header>
                    <div class="now-playing-container">
                        <div
                            class="now-playing-poster"
                            :style="'background-image: url(' + nowPlayingPoster + ')'"
                        ></div>
                    </div>
                    <div class="now-playing-footer">
                        <div class="content-rating" v-if="settings.show_mpaa_rating">
                            <img
                                v-if="contentRating"
                                :src="'/images/' + contentRating + '.svg'"
                                alt="Content Rating"
                                v-cloak
                            />
                        </div>

                        <div class="dolby-logos" v-if="settings.show_processing_logos">
                            <img
                                class="imax"
                                src="/images/imax.png"
                                alt="IMAX"
                                v-if="settings.show_imax"
                            />
                            <img
                                class="auro3d"
                                src="/images/auro3d.svg"
                                alt="Auro 3D"
                                v-if="settings.show_auro_3d"
                            />
                            <img
                                src="/images/dolby-vision-stacked.svg"
                                alt="Dolby Vision"
                                class="dolby-vision-stacked"
                                v-if="settings.show_dolby_vision_vertical"
                            />
                            <img
                                src="/images/dolby-atmos-stacked.svg"
                                alt="Dolby Atmos"
                                class="dolby-atmos-stacked"
                                v-if="settings.show_dolby_atmos_vertical"
                            />
                            <img
                                class="dts"
                                src="/images/dts-x.svg"
                                alt="DTS"
                                v-if="settings.show_dts"
                            />
                        </div>
                        <div class="audience-rating" v-if="settings.show_audience_rating">
                            <star-rating
                                :increment="0.1"
                                :max-rating="5"
                                inactive-color="#000"
                                active-color="#fff"
                                :star-size="28"
                                v-model:rating="rating"
                                border-color="#fff"
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

const $recentAdded = document.querySelector('#recent-added-container');
let $video = document.getElementById('youtube-player');

export default {
    data: function () {
        return {};
    },
    components: {
        StarRating,
    },
    watch: {
        nowPlaying: {
            handler: function (val, oldVal) {
                if (val) {
                    this.stopMusic();
                    this.getNowPlaying();
                    this.stopTransitionImages();
                    this.controlTV('on');
                } else {
                    this.startTransitionImages();
                    this.setNowPlayingPoster(null);
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
            'theme_music',
            'runtime',
            'mpaaRating',
            'show_imax',
            'show_auro_3d',
            'show_dolby_vision_vertical',
            'show_dolby_atmos_vertical',
            'show_dolby_51',
            'show_dts',
            'socket',
        ]),
    },
    methods: {
        ...mapActions(usePostersStore, [
            'boot',
            'stopTransitionImages',
            'startTransitionImages',
            'controlTV',
            'setLoading',
            'setSocket',
            'setNowPlayingPoster',
            'setVideoPlayerRef',
            'playMusic',
            'stopMusic',
            'playTrailer',
            'playVideo',
        ]),
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
        this.setVideoPlayerRef(this.$refs.videoPlayer);
        if (typeof io !== 'undefined') {
            this.setSocket();
        }
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

.loading-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #fff;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    background-color: #000;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
}
#recent-added-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.recent-poster-container,
.now-playing-container {
    width: 1060px;
    height: 1589px;
    max-height: 1589px;
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
    height: 580px;
    width: 100%;
    position: absolute;
    left: 0;
    bottom: -44px;
    z-index: 4;

    #youtube-player {
        width: 100%;
        height: 100%;

        iframe {
            width: 100%;
            height: 100%;
        }
    }
}

.recent-poster {
    width: 1060px;
    height: 1590px;
    opacity: 0;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    position: absolute;
    top: 0;
    backface-visibility: hidden;
    will-change: opacity;
    perspective: 1000;
    transform: translateZ(0);

    &.hide {
        animation: FadeOut 2.5s ease-out forwards;
        z-index: 3;
    }

    &.show {
        animation: FadeIn 2.5s ease-in forwards;
        z-index: 2;
    }

    &.has-trailer {
        width: 702px;
        height: 1052px;
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

.vertical-items {
    .item {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
        backface-visibility: hidden;
        will-change: transform;
        perspective: 1000;
        transform: translate3d(0, 100%, 0);

        transition: transform 1.5s ease-in-out;

        &.next-item {
            z-index: -1;
        }

        &.active-item {
            transform: translate3d(0, 0, 0);
            z-index: 10;
        }

        &.past-item {
            transform: translate3d(0, -100%, 0);
            z-index: 10;
        }
    }
}

#now-playing-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.now-playing-poster {
    width: 1060px;
    height: 1590px;
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
    flex-grow: 1;
    max-height: 190px;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;
    position: relative;

    .runtime {
        position: absolute;
        top: 60px;
        left: 40px;
        color: #fff;
        font-size: 32px;
        font-weight: 400;
    }

    h1 {
        text-transform: uppercase;
        padding: 12px 24px 14px 24px;
        border: 4px solid #fff;
        font-size: 56px;
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
    min-height: 150px;
    max-height: 150px;
    display: flex;
    flex-grow: 1;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.content-rating {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-right: auto;
    flex-grow: 1;
    min-width: 230px;
    max-width: 230px;

    img {
        width: 100%;
        max-width: 220px;
        height: auto;
    }
}

.audience-rating {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
    flex-grow: 1;
    min-width: 200px;
    max-width: 200px;
}

.dolby-logos {
    flex-grow: 2;
    padding: 0 18px;
    display: flex;
    align-items: center;
    justify-content: center;

    div {
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    img {
        margin: 0 12px;
    }
}

.dolby-atmos {
    width: 100%;
    max-width: 180px;
    height: auto;
}
.dolby-vision {
    width: 100%;
    max-width: 180px;
    height: auto;
}

.dolby-atmos-stacked {
    width: 100%;
    max-width: 130px;
    height: auto;
}
.dolby-vision-stacked {
    width: 100%;
    max-width: 130px;
    height: auto;
}

.dts {
    width: 100%;
    max-width: 130px;
    height: auto;
}

.imax {
    width: 100%;
    max-width: 130px;
    height: auto;
}
.auro3d {
    width: 100%;
    max-width: 140px;
    height: auto;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 2s;
}
.fade-enter,
.fade-leave-to {
    opacity: 0;
}

.fade2-enter-active,
.fade2-leave-active {
    transition: opacity 1s;
}
.fade2-enter,
.fade2-leave-to {
    opacity: 0;
}

.vue-star-rating {
    margin-top: -4px;
}

.vue-star-rating-star {
    margin-right: 4px !important;
}

@keyframes FadeIn {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

@keyframes FadeOut {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}
</style>
