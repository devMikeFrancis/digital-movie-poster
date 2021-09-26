<template>
    <div>
        <div class="loading-overlay" v-if="loading">
            <div @click="gotoPosters()">{{ loadingMessage }}</div>
        </div>
        <div id="recent-added-container" @click.prevent="gotoPosters()" v-if="!nowPlaying">
            <header class="coming-soon-header">
                <h1>{{ settings.coming_soon_text }}</h1>
            </header>
            <div class="recent-poster-container">
                <div
                    v-for="(poster, index) in moviePosters"
                    class="recent-poster"
                    v-bind:class="{ show: poster.show, hide: !poster.show }"
                    :style="'background-image: url(storage/posters/' + poster.file_name + ')'"
                    v-bind:key="`key-${index}`"
                ></div>
            </div>
            <footer class="coming-soon-footer">
                <div class="dolby-logos" v-if="settings.show_processing_logos">
                    <img class="imax" src="/images/imax.png" alt="IMAX" v-if="settings.show_imax" />
                    <img
                        src="/images/dolby-vision.svg"
                        class="dolby-vision"
                        alt="Dolby Vision"
                        v-if="settings.show_dolby_vision_horizontal"
                    />
                    <img
                        src="/images/dolby-atmos.svg"
                        class="dolby-atmos"
                        alt="Dolby Atmos"
                        v-if="settings.show_dolby_atmos_horizontal"
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
                    <img class="dts" src="/images/dts-x.svg" alt="DTS" v-if="settings.show_dts" />
                </div>
            </footer>
        </div>
        <transition name="fade" mode="out-in">
            <div id="now-playing-container" v-if="nowPlaying" @click.prevent="gotoPosters()">
                <header class="now-playing-header">
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
                            src="/images/dolby-vision.svg"
                            class="dolby-vision"
                            alt="Dolby Vision"
                            v-if="settings.show_dolby_vision_horizontal"
                        />
                        <img
                            src="/images/dolby-atmos.svg"
                            class="dolby-atmos"
                            alt="Dolby Atmos"
                            v-if="settings.show_dolby_atmos_horizontal"
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
                            v-bind:increment="0.1"
                            v-bind:max-rating="5"
                            inactive-color="#000"
                            active-color="#fff"
                            v-bind:star-size="24"
                            v-bind:rating="rating"
                            border-color="#fff"
                            :border-width="borderWidth"
                            v-bind:show-rating="false"
                            v-bind:read-only="true"
                        />
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import Api from '../services/api';
import StarRating from 'vue-star-rating';
import EventEmitter from 'eventemitter3';

const $recentAdded = document.querySelector('#recent-added-container');

export default {
    data: function () {
        return {
            loading: true,
            loadingMessage: 'Loading Posters ...',
            isConnected: false,
            baseUrl: '',
            plexToken: '',
            moviesQueue: [],
            moviePosters: [],
            nowPlayingPoster: '',
            recentlyAddedInterval: null,
            nowPlayingInterval: null,
            transitionImagesInterval: null,
            nowPlaying: false,
            contentRating: '',
            rating: 0,
            currentImage: 0,
            borderWidth: 2,
            starPadding: 2,
            controller: '',
            settings: {
                poster_display_speed: 15000,
            },
        };
    },
    components: {
        StarRating,
    },
    watch: {
        nowPlaying: {
            handler: function (val, oldVal) {
                if (val) {
                    this.getNowPlaying();
                    this.stopTransitionImages();
                    this.controlTV('on');
                } else {
                    this.startTransitionImages();
                    this.nowPlayingPoster = null;
                }
            },
            deep: true,
        },
    },
    methods: {
        boot() {
            axios
                .get('/api/settings')
                .then((response) => {
                    this.settings = response.data;
                    if (this.settings.plex_service) {
                        this.startSockets();
                        localStorage.setItem('plexIpAddress', this.settings.plex_ip_address);
                        localStorage.setItem('plexToken', this.settings.plex_token);
                    }
                    this.getMoviePosters();
                    this.recentlyAddedInterval = setInterval(() => {
                        this.cachePosters();
                    }, 60000 * 60 * 60);
                    this.controlTV('on');
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        getMoviePosters() {
            this.stopTransitionImages();
            axios
                .get('/api/posters?show_in_rotation=true')
                .then((response) => {
                    this.moviePosters = response.data.posters;

                    if (this.moviePosters.length === 0) {
                        this.loadingMessage =
                            'You do not have any posters loaded yet. Click here to manage your poster library.';
                    } else {
                        if (this.settings.random_order) {
                            this.moviePosters[
                                Math.floor(Math.random() * this.moviePosters.length)
                            ].show = true;
                        } else {
                            this.moviePosters[0].show = true;
                        }

                        setTimeout(() => {
                            this.loading = false;
                            this.startTransitionImages();
                        }, 12000);
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        cachePosters() {
            axios
                .get('/api/cache-posters')
                .then((response) => {
                    this.moviePosters = response.data.posters;
                    setTimeout(() => {
                        if (this.loading === true) {
                            this.loading = false;
                            this.startTransitionImages();
                        }
                    }, 5000);
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        getNowPlaying() {
            console.log('GET NOW PLAYING');
            Api.apiCallPlex('/status/sessions/')
                .then((response) => {
                    const size = response.data.MediaContainer.size;
                    if (size > 0) {
                        this.nowPlayingPoster =
                            'http://' +
                            this.settings.plex_ip_address +
                            ':32400' +
                            response.data.MediaContainer.Metadata[0].thumb +
                            '?X-Plex-Token=' +
                            this.settings.plex_token;
                        this.contentRating = response.data.MediaContainer.Metadata[0].contentRating;
                        this.rating = response.data.MediaContainer.Metadata[0].audienceRating / 2;
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        transitionImages() {
            let poster = '';
            let activeIndex = 0;
            if (this.moviePosters.length > 0) {
                if (this.settings.random_order) {
                    this.moviePosters.forEach((item) => {
                        if (item.show) {
                            item.show = false;
                        }
                    });
                    poster =
                        this.moviePosters[Math.floor(Math.random() * this.moviePosters.length)];
                } else {
                    const len = this.moviePosters.length;
                    const currItem = this.moviePosters.filter(function (e, i) {
                        return e.show === true;
                    });
                    const currIndex = this.moviePosters.indexOf(currItem[0]);
                    this.moviePosters[currIndex].show = false;
                    activeIndex = currIndex + 1 === len ? 0 : currIndex + 1;
                    poster = this.moviePosters[activeIndex];
                }
                poster.show = true;
            }
        },
        startTransitionImages() {
            this.transitionImagesInterval = setInterval(() => {
                this.transitionImages();
            }, this.settings.poster_display_speed);
        },
        stopTransitionImages() {
            clearInterval(this.transitionImagesInterval);
        },
        controlTV(command) {
            if (this.settings.user_cec_power) {
                if (!this.isOnTime()) {
                    if (command === 'on') {
                        command = 'standby';
                    }
                }
                axios
                    .get('/api/control-display/' + command)
                    .then(() => {})
                    .catch((e) => {
                        console.log(e.message);
                    });
            }
        },
        controlPlayerState(state) {
            switch (state) {
                case 'playing':
                    this.nowPlaying = true;
                    break;
                case 'paused':
                case 'stopped':
                    this.nowPlaying = false;
                    break;
            }
        },
        isOnTime() {
            let presentDate = new Date();
            presentDate = this.changeTimezone(presentDate, 'America/New_York');
            let date = new Date();
            date = this.changeTimezone(date, 'America/New_York');
            const month = date.getMonth() + 1;
            const day = date.getDate();
            const year = date.getFullYear();
            const date1 = new Date(
                month + '/' + day + '/' + year + ' ' + this.settings.start_power_time
            );
            const date2 = new Date(
                month + '/' + day + '/' + year + ' ' + this.settings.end_power_time
            );

            if (
                presentDate.getTime() > date1.getTime() &&
                presentDate.getTime() < date2.getTime()
            ) {
                return true;
            } else {
                return false;
            }
        },
        changeTimezone(date, ianatz) {
            var invdate = new Date(
                date.toLocaleString('en-US', {
                    timeZone: ianatz,
                })
            );
            var diff = date.getTime() - invdate.getTime();
            return new Date(date.getTime() - diff);
        },
        startSockets() {
            const socket = new WebSocket(
                'ws://' +
                    this.settings.plex_ip_address +
                    ':32400/:/websockets/notifications' +
                    '?X-Plex-Token=' +
                    this.settings.plex_token
            );

            socket.addEventListener('open', () => {});

            socket.addEventListener('message', (event) => {
                const data = JSON.parse(event.data);
                const action = data.NotificationContainer.type;
                if (action === 'playing') {
                    const state = data.NotificationContainer.PlaySessionStateNotification[0].state;
                    console.log(state); // playing || stopped
                    this.controlPlayerState(state);
                }
            });
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
        this.loading = true;
    },
};
</script>

<style lang="scss">
body {
    background: #000;
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

.recent-poster {
    width: 1060px;
    height: 1590px;
    opacity: 0;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    position: absolute;
    top: 0;
    //transition: opacity 2.5s ease-in-out;

    &.hide {
        animation: FadeOut 2.5s ease-out forwards;
        z-index: 3;
    }

    &.show {
        //opacity: 1;
        //transition: opacity 1s ease-in-out;
        animation: FadeIn 2.5s ease-in forwards;
        z-index: 2;
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
    display: flex;
    flex-grow: 1;
    max-height: 190px;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;

    h1 {
        text-transform: uppercase;
        padding: 12px 24px 14px 24px;
        border: 4px solid #fff;
        font-size: 80px;
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
}

.dolby-logos {
    display: flex;
    align-items: center;
    justify-content: center;

    img {
        margin: 0 6px;
    }
}

.dolby-atmos {
    width: 100%;
    max-width: 200px;
    height: auto;
}
.dolby-vision {
    width: 100%;
    max-width: 200px;
    height: auto;
}

.dolby-atmos-stacked {
    width: 100%;
    max-width: 160px;
    height: auto;
}
.dolby-vision-stacked {
    width: 100%;
    max-width: 160px;
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
