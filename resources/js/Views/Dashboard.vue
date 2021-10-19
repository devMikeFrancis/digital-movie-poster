<template>
    <div>
        <div class="movie-posters">
            <div class="loading-overlay" v-if="loading">
                <div class="p-12" @click="gotoPosters()">{{ loadingMessage }}</div>
            </div>
            <div id="recent-added-container" @click.prevent="gotoPosters()" v-if="!nowPlaying">
                <header class="coming-soon-header">
                    <span class="runtime" v-if="settings.show_runtime && runtime"
                        >{{ runtime }} min</span
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
                        <star-rating
                            v-if="audienceRating"
                            v-bind:increment="0.1"
                            v-bind:max-rating="5"
                            inactive-color="#000"
                            active-color="#fff"
                            v-bind:star-size="28"
                            v-bind:rating="audienceRating"
                            border-color="#fff"
                            :border-width="borderWidth"
                            v-bind:show-rating="false"
                            v-bind:read-only="true"
                        />
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
                                v-bind:increment="0.1"
                                v-bind:max-rating="5"
                                inactive-color="#000"
                                active-color="#fff"
                                v-bind:star-size="28"
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
    </div>
</template>

<script>
import Api from '../services/api';
import StarRating from 'vue-star-rating';
import EventEmitter from 'eventemitter3';

const $recentAdded = document.querySelector('#recent-added-container');
let $video = document.getElementById('youtube-player');

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
            mpaaRating: '',
            rating: 0,
            audienceRating: 0,
            currentImage: 0,
            borderWidth: 2,
            starPadding: 2,
            controller: '',
            settings: {
                poster_display_speed: 15000,
                transition_type: 'fade',
            },
            videoPlaying: false,
            iframeEl: '',
            audio: null,
            runtime: '',
            theme_music: null,
            show_dolby_atmos_vertical: false,
            show_dolby_vision_vertical: false,
            show_dts: false,
            show_auro_3d: false,
            show_imax: false,
            show_dolby_51: false,
            socket: '',
        };
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
                    }, 60000 * 60 * 60 * 1000 * 4); // Every 4 hours
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
                    let poster = '';
                    if (this.moviePosters.length === 0) {
                        this.loadingMessage =
                            'You do not have any posters loaded yet. Open this application in a browser and click here to manage your poster library.';
                    } else {
                        if (this.settings.random_order) {
                            poster = this.getRandomPoster();
                        } else {
                            poster = this.moviePosters[0];
                            poster.activeItem = true;
                        }

                        poster.show = true;

                        this.handlePosterView(poster);

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
        handlePosterView(poster) {
            this.mpaaRating = poster.mpaa_rating;
            if (poster.audience_rating) {
                this.audienceRating = poster.audience_rating / 2;
            }
            if (poster.trailer_path && poster.show_trailer) {
                this.playTrailer(poster.trailer_path);
            }
            if (poster.show_runtime) {
                this.runtime = poster.runtime;
            }
            if (
                poster.play_theme_music &&
                poster.theme_music_path &&
                this.settings.play_theme_music
            ) {
                this.theme_music = poster.theme_music_path;
                this.playMusic();
            }

            if (!this.settings.use_global_prologos) {
                if (this.settings.use_global_prologos_if_no_poster_prologos) {
                    if (
                        !poster.show_dolby_atmos &&
                        !poster.show_dolby_vision &&
                        !poster.show_dtsx &&
                        !poster.show_auro_3d &&
                        !poster.show_imax &&
                        !poster.show_dolby_51
                    ) {
                        this.useSettingsProLogos();
                    } else {
                        this.usePosterProLogos(poster);
                    }
                } else {
                    this.usePosterProLogos(poster);
                }
            } else {
                this.useSettingsProLogos();
            }
        },
        usePosterProLogos(poster) {
            this.show_dolby_atmos_vertical = poster.show_dolby_atmos;
            this.show_dolby_vision_vertical = poster.show_dolby_vision;
            this.show_dts = poster.show_dtsx;
            this.show_auro_3d = poster.show_auro_3d;
            this.show_imax = poster.show_imax;
            this.show_dolby_51 = poster.show_dolby_51;
        },
        useSettingsProLogos() {
            this.show_dolby_atmos_vertical = this.settings.show_dolby_atmos_vertical;
            this.show_dolby_vision_vertical = this.settings.show_dolby_vision_vertical;
            this.show_dts = this.settings.show_dts;
            this.show_auro_3d = this.settings.show_auro_3d;
            this.show_imax = this.settings.show_imax;
            this.show_dolby_51 = this.settings.show_dolby_51;
        },
        getNowPlaying() {
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

                        let data = response.data.MediaContainer.Metadata[0];
                        this.contentRating = data.contentRating;

                        if (data.audienceRating) {
                            this.rating = data.audienceRating / 2;
                        }

                        if (data.duration && this.settings.show_runtime) {
                            this.runtime = data.duration / 1000 / 60;
                        }
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        transitionImages() {
            let poster = '';
            if (this.$refs.videoPlayer) {
                this.$refs.videoPlayer.innerHTML = '';
            }
            this.stopMusic();

            if (this.moviePosters.length > 0) {
                if (this.settings.random_order) {
                    poster = this.getRandomPoster();
                } else {
                    poster = this.getInSequencePoster();
                }
                poster.show = true;
                this.handlePosterView(poster);
            }
        },
        startTransitionImages() {
            window.transitionImagesInterval = setInterval(() => {
                this.transitionImages();
            }, this.settings.poster_display_speed);
        },
        stopTransitionImages() {
            clearInterval(window.transitionImagesInterval);
        },
        getRandomPoster() {
            const currItem = this.moviePosters.filter(function (e, i) {
                return e.show === true;
            });

            const currIndex = this.moviePosters.indexOf(currItem[0]);

            this.moviePosters.forEach((item) => {
                if (item.show) {
                    item.show = false;
                }
            });

            const randIndex = Math.floor(Math.random() * this.moviePosters.length);

            let poster = this.moviePosters[randIndex];
            let pastPoster = this.moviePosters[currIndex];

            poster.activeItem = true;
            pastPoster.pastItem = true;
            pastPoster.activeItem = false;

            setTimeOut(() => {
                pastPoster.pastItem = false;
            }, 3000);

            return poster;
        },
        getInSequencePoster() {
            const len = this.moviePosters.length;

            // Current/Past Item
            const currItem = this.moviePosters.filter(function (e, i) {
                return e.show === true;
            });
            const currIndex = this.moviePosters.indexOf(currItem[0]);
            this.moviePosters[currIndex].show = false;

            // Next/Active Item
            const activeIndex = currIndex + 1 === len ? 0 : currIndex + 1;

            // For vertical transition
            let nextIndex = currIndex === 0 ? len - 1 : currIndex - 1;

            //let nextPoster = this.moviePoster[nextIndex];
            let poster = this.moviePosters[activeIndex];
            let pastPoster = this.moviePosters[currIndex];

            poster.activeItem = true;
            pastPoster.pastItem = true;
            pastPoster.activeItem = false;

            setTimeout(() => {
                pastPoster.pastItem = false;
            }, 3000);
            // END FOR VERTICAL TRANSITION

            return poster;
        },
        controlTV(command) {
            if (this.settings.use_cec_power) {
                if (!this.isOnTime()) {
                    if (command === 'on') {
                        command = 'standby';
                    }
                }
                axios
                    .get('/api/control-display/' + command)
                    .then((response) => {
                        console.log(response.data);
                    })
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
        playMusic() {
            setTimeout(() => {
                window.audio = new Audio('/storage/music/' + this.theme_music);
                window.audio.play();
            }, 1500);
        },
        stopMusic() {
            if (window.audio) {
                let vol = 1;
                let interval = 40;
                if (window.audio.volume == 1) {
                    var intervalID = setInterval(() => {
                        if (vol > 0) {
                            vol -= 0.05;
                            window.audio.volume = vol.toFixed(2);
                        } else {
                            clearInterval(intervalID);
                            window.audio.pause();
                            window.audio = null;
                        }
                    }, interval);
                }
            }
        },
        playTrailer(youTubeId) {
            this.iframeEl = document.createElement('iframe');
            this.iframeEl.setAttribute(
                'src',
                `https://www.youtube.com/embed/${youTubeId}?enablejsapi=1&autoplay=1&mute=1&autohide=2&modestbranding=1&showinfo=0&controls=0&rel=0&border=0&wmode=opaque`
            );
            this.iframeEl.setAttribute('frameborder', '0');
            this.iframeEl.setAttribute('allow', 'autoplay; encrypted-media;');
            this.iframeEl.addEventListener('load', this.playVideo);
            this.$refs.videoPlayer.appendChild(this.iframeEl);
            this.iframeEl.focus();
        },
        playVideo(e) {
            this.videoPlaying = true;
            this.iframeEl.contentWindow.postMessage(
                '{"event":"command","func":"playVideo","args":""}',
                '*'
            );
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
        reload() {
            this.stopTransitionImages();
            clearInterval(this.recentlyAddedInterval);
            this.stopMusic();
            this.videoPlaying = false;
            this.boot();
        },
    },
    created() {
        this.boot();
    },
    mounted() {
        this.loading = true;
        if (typeof io !== 'undefined') {
            this.socket = io('http://movieposter.local:3000');

            this.socket.on('receive:command', (data) => {
                switch (data.command) {
                    case 'reload':
                        this.reload();
                        break;
                }
            });
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

    &.has-trailer {
        width: 702px;
        height: 1052px;
        left: 50%;
        transform: translateX(-50%);
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
        transform: translateY(100%);

        transition: transform 1.5s cubic-bezier(0.33, 1, 0.68, 1);

        &.next-item {
            z-index: -1;
        }

        &.active-item {
            transform: translateY(0);
            z-index: 10;
        }

        &.past-item {
            transform: translateY(-100%);
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
        top: 50px;
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
