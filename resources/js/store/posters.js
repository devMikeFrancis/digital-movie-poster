import axios from 'axios';
import { io } from 'socket.io-client';
import { defineStore } from 'pinia';

/** How often a running display pulls the poster library again. */
const POSTER_SYNC_MS = 1000 * 60 * 60 * 4;

export const usePostersStore = defineStore('posters', {
    state: () => ({
        loading: true,
        loadingMessage: 'Loading<br/>Posters ...',
        bootTime: 5000,
        settingsIntervalTime: 10000,
        reloadPostersIntervalTime: 20000,
        moviePosters: [],
        moviesQueue: [],
        isConnected: false,
        baseUrl: '',
        settings: {
            poster_display_speed: 15000,
            transition_type: 'fade',
        },
        nowPlayingPoster: '',
        settingsInterval: null,

        recentlyAddedInterval: null,
        nowPlayingInterval: null,
        nowPlayingIntervalTime: 5000,
        jellyfinDevicePlaying: null,
        servicePlaying: null,
        canRefreshTransitionTime: false,
        transitionImagesInterval: null,
        contentRating: '',
        mpaaRating: '',

        // Which poster is on screen. The header and footer key their
        // transitions off it so the details change with the artwork.
        currentPosterId: null,

        rating: 0,
        audienceRating: 0,
        currentImage: 0,
        borderWidth: 2,
        starPadding: 2,
        controller: '',
        iframeEl: '',
        audio: null,
        runtime: 0,
        nowPlayingRuntime: 0,
        theme_music: null,
        nowPlaying: false,
        isPlaying: false,
        videoPlaying: false,
        show_dolby_atmos_vertical: false,
        show_dolby_vision_vertical: false,
        show_dts: false,
        show_auro_3d: false,
        show_imax: false,
        show_dolby_51: false,
        socket: '',
        checkedPlexMediaType: false,
        pgLimits: ['G', 'PG'],
        pg13Limits: ['G', 'PG', 'PG-13'],
        rLimits: ['G', 'PG', 'PG-13', 'R'],
        nc17Limits: ['G', 'PG', 'PG-13', 'R', 'NC-17'],
        tvMaLimits: ['TV-Y', 'TV-Y7', 'TV-Y7 FV', 'TV-G', 'TV-PG', 'TV-14', 'TV-MA'],
        tv14Limits: ['TV-Y', 'TV-Y7', 'TV-Y7 FV', 'TV-G', 'TV-PG', 'TV-14'],
        tvPgLimits: ['TV-Y', 'TV-Y7', 'TV-Y7 FV', 'TV-G', 'TV-PG'],
        tvGLimits: ['TV-Y', 'TV-Y7', 'TV-Y7 FV', 'TV-G'],
        tvY7fvLimits: ['TV-Y', 'TV-Y7', 'TV-Y7 FV'],
        tvY7Limits: ['TV-Y', 'TV-Y7'],
    }),
    getters: {
        /**
         * The class prefix for the chosen transition. Held here so the poster,
         * the header and the footer cannot drift apart on which effect they are
         * running - they have to move together to look like one change.
         *
         * 'vertical' keeps the older 'slide' prefix so displays that already
         * chose it are unaffected.
         */
        transitionPrefix() {
            const prefixes = {
                fade: 'fade',
                vertical: 'slide',
                crossfade: 'crossfade',
                cut: 'cut',
            };

            return prefixes[this.settings.transition_type] || 'fade';
        },
        mediaPosters() {
            return this.moviePosters.filter((poster) => {
                if (poster.media_type === 'movie') {
                    return this.withinMpaaLimit(poster.mpaa_rating);
                }
                if (poster.media_type === 'tv') {
                    return this.withinTvLimit(poster.mpaa_rating);
                }
            });
        },
    },
    actions: {
        boot() {
            console.log('--- BOOTING ---');
            this.getSettings().then(() => {
                this.getMoviePosters();
                this.startSyncPosters();
                setTimeout(() => {
                    this.canRefreshTransitionTime = true;
                    this.startSettingsInterval();
                }, this.settingsIntervalTime);
                setTimeout(() => {
                    this.startSockets();
                }, this.bootTime + 3000);
            });
        },
        getSettings() {
            return axios
                .get('/api/settings')
                .then((response) => {
                    console.log('-- GET SETTINGS');
                    if (
                        this.canRefreshTransitionTime &&
                        this.settings.poster_display_speed !== response.data.poster_display_speed
                    ) {
                        console.log('RESETTING TRANSITION TIME');
                        this.stopTransitionImages();
                        setTimeout(() => {
                            this.startTransitionImages();
                        }, 1000);
                    }
                    this.settings = response.data;
                    return response;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        startSettingsInterval() {
            console.log('START SETTINGS INTERVAL');
            this.settingsInterval = setInterval(() => {
                this.getSettings();
            }, this.settingsIntervalTime);
        },
        stopSettingsInterval() {
            console.log('STOP SETTINGS INTERVAL');
            clearInterval(this.settingsInterval);
        },
        getMoviePosters() {
            console.log('GET MOVIE POSTERS');
            this.stopTransitionImages();
            axios
                .get('/api/posters?show_in_rotation=true')
                .then((response) => {
                    this.moviePosters = response.data.posters;
                    if (this.moviePosters.length === 0) {
                        this.loadingMessage =
                            'You do not have any posters loaded yet.<br/>Visit http://Your IP Address/posters to start.';
                    } else {
                        this.setInitialPosterView();
                        this.bootReady();
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        reloadMoviePosters() {
            console.log('RELOADING MOVIE POSTERS');
            axios
                .get('/api/posters?show_in_rotation=true')
                .then((response) => {
                    let posters = response.data.posters;
                    if (this.moviePosters.length === 0 && posters.length > 0) {
                        this.moviePosters = posters;
                        this.loading = false;
                        this.loadingMessage = 'Loading<br />Posters ...';
                        this.setInitialPosterView();
                        setTimeout(() => {
                            this.startTransitionImages();
                        }, 250);
                    } else {
                        this.moviePosters = posters;
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        setInitialPosterView() {
            // A rating limit can exclude every poster, and unrated ones are
            // excluded too - so there is not always a first poster to show.
            // Reaching for one regardless threw during boot and left the
            // display blank for good rather than for one change.
            if (this.mediaPosters.length === 0) {
                return;
            }

            const poster = this.settings.random_order
                ? this.mediaPosters[this.getRandomPoster()]
                : this.mediaPosters[0];

            poster.show = true;

            this.handlePosterView(poster);
        },
        bootReady() {
            setTimeout(() => {
                this.loading = false;
                this.loadingMessage = 'Loading<br />Posters ...';
                this.startTransitionImages();
            }, this.bootTime);
        },
        handlePosterView(poster) {
            console.log('HANDLE POSTER VIEW');
            this.currentPosterId = poster.id;
            this.mpaaRating = poster.mpaa_rating;
            if (poster.audience_rating) {
                this.audienceRating = poster.audience_rating / 2;
            }
            if (poster.trailer_path && poster.show_trailer) {
                if (typeof this.videoPlayer !== 'undefined') {
                    this.playTrailer(poster.trailer_path);
                }
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
        // Four hours, which is what the arithmetic here was meant to say. It
        // came to a little over twenty-seven years, so a display never picked
        // up a poster added after it started - the screen kept cycling whatever
        // was in the library the last time it loaded.
        startSyncPosters() {
            this.recentlyAddedInterval = setInterval(() => {
                this.cachePosters();
            }, POSTER_SYNC_MS);
        },
        /**
         * Tells any display on the network to pull the library again. The same
         * command the Refresh Movie Posters button sends, so that adding a
         * poster does not need a second, manual step to be seen.
         */
        requestDisplayReload() {
            if (this.socket && typeof this.socket.emit === 'function') {
                this.socket.emit('dispatch:command', { command: 'reload' });
            }
        },
        withinMpaaLimit(rating) {
            let mpaaLimit = this.settings.mpaa_limit;
            if (!mpaaLimit) {
                return true;
            }
            if (mpaaLimit === 'G') {
                // Was reading an undefined 'poster' rather than the rating
                // passed in, so choosing the strictest limit threw inside the
                // filter and took the whole poster list with it.
                return rating === 'G';
            }
            if (mpaaLimit === 'PG') {
                return this.pgLimits.includes(rating);
            }
            if (mpaaLimit === 'PG-13') {
                return this.pg13Limits.includes(rating);
            }
            if (mpaaLimit === 'R') {
                return this.rLimits.includes(rating);
            }
            if (mpaaLimit === 'NC-17') {
                return this.nc17Limits.includes(rating);
            }
            return true;
        },
        withinTvLimit(rating) {
            let mpaaLimit = this.settings.tv_limit;
            if (!mpaaLimit) {
                return true;
            }
            if (mpaaLimit === 'TV-Y') {
                return rating === 'TV-Y';
            }
            if (mpaaLimit === 'TV-Y7') {
                return this.tvY7Limits.includes(rating);
            }
            if (mpaaLimit === 'TV-Y7 FV') {
                return this.tvY7fvLimits.includes(rating);
            }
            if (mpaaLimit === 'TV-G') {
                return this.tvGLimits.includes(rating);
            }
            if (mpaaLimit === 'TV-PG') {
                return this.tvPgLimits.includes(rating);
            }
            if (mpaaLimit === 'TV-14') {
                return this.tv14Limits.includes(rating);
            }
            if (mpaaLimit === 'TV-MA') {
                return this.tvMaLimits.includes(rating);
            }
            return true;
        },
        enabledMediaServices() {
            return ['plex', 'jellyfin', 'kodi'].filter(
                (service) => this.settings[service + '_service']
            );
        },
        getNowPlaying() {
            if (this.servicePlaying) {
                this.fetchNowPlaying(this.servicePlaying);
            }
        },
        /**
         * Ask our own backend what a media server is playing.
         *
         * The response is already normalised, and `poster` points at the
         * artwork proxy on this server rather than at the media server, so no
         * token is needed in the browser.
         */
        fetchNowPlaying(service) {
            return axios
                .get('/api/now-playing/' + service)
                .then(({ data }) => {
                    if (!data.playing) {
                        if (this.servicePlaying === service) {
                            this.servicePlaying = null;
                            this.controlPlayerState('stopped');
                        }
                        return;
                    }

                    if (service === 'plex' && !this.plexMediaTypeAllowed(data.mediaType)) {
                        return;
                    }

                    this.servicePlaying = service;
                    this.controlPlayerState('playing');
                    this.setNowPlaying(data);
                })
                .catch(() => {});
        },
        plexMediaTypeAllowed(mediaType) {
            if (mediaType === 'movie') {
                return this.settings.plex_show_movie_now_playing;
            }
            if (mediaType === 'show' || mediaType === 'episode') {
                return this.settings.plex_show_tv_now_playing;
            }
            return true;
        },
        setNowPlaying(data) {
            let withinMpaaLimit = this.withinMpaaLimit(data.contentRating);
            let withinTvLimit = this.withinTvLimit(data.contentRating);
            if (withinMpaaLimit && withinTvLimit) {
                console.log('SET NOW PLAYING');
                this.nowPlayingPoster = data.poster;
                this.contentRating = data.contentRating;

                if (data.audienceRating) {
                    this.rating = data.audienceRating / 2;
                }

                if (data.duration && this.settings.show_runtime) {
                    this.nowPlayingRuntime = data.duration;
                }
                this.isPlaying = true;
            } else {
                this.isPlaying = false;
                this.contentRating = 0;
            }
        },
        setIsPlaying(state) {
            this.isPlaying = state;
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
        /**
         * An index into the posters actually on show.
         *
         * Drawn against moviePosters before, which is the whole library: with a
         * rating limit set the shown list is shorter, so the index could land
         * past its end and leave nothing to show at all.
         *
         * Never returns the poster already up either. Doing so meant showing
         * and then hiding the same object, which left the screen blank until
         * the next change came round.
         */
        getRandomPoster(currentIndex = -1) {
            const length = this.mediaPosters.length;

            if (length <= 1) {
                return 0;
            }

            const index = Math.floor(Math.random() * length);

            return index === currentIndex ? (index + 1) % length : index;
        },
        getInSequencePoster() {
            console.log('GET NEXT POSTER');
            const posters = this.mediaPosters;
            const len = posters.length;

            if (len === 0) {
                return null;
            }

            const currIndex = posters.findIndex((poster) => poster.show === true);
            const activeIndex = this.settings.random_order
                ? this.getRandomPoster(currIndex)
                : (currIndex + 1) % len;

            const poster = posters[activeIndex];
            const pastPoster = currIndex > -1 ? posters[currIndex] : null;

            // Hidden first, and never the poster about to be shown: the two
            // used to be the same object whenever the random pick landed on the
            // poster already up, and clearing it afterwards left the screen
            // blank until the next change.
            if (pastPoster && pastPoster !== poster) {
                pastPoster.show = false;
            }

            poster.show = true;

            return poster;
        },
        transitionImages() {
            console.log('TRANSITION IMAGES');
            let poster = '';
            if (this.videoPlayer) {
                this.videoPlayer.innerHTML = '';
            }
            this.stopMusic();

            if (this.mediaPosters.length > 0) {
                poster = this.getInSequencePoster();
                this.handlePosterView(poster);
            }
        },
        cachePosters() {
            console.log('SYNCING POSTERS');
            axios
                .get('/api/cache-posters')
                .then((response) => {
                    this.stopTransitionImages();
                    this.moviePosters = response.data.posters;
                    setTimeout(() => {
                        if (this.loading === true) {
                            this.loading = false;
                            this.startTransitionImages();
                        }
                    }, this.bootTime);
                })
                .catch((e) => {
                    console.log(e.message);
                });
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
            this.videoPlayer.appendChild(this.iframeEl);
            this.iframeEl.focus();
        },
        playVideo(e) {
            this.videoPlaying = true;
            this.iframeEl.contentWindow.postMessage(
                '{"event":"command","func":"playVideo","args":""}',
                '*'
            );
        },
        updateSetting(poster, column, value) {
            const params = {
                _method: 'put',
                value: value,
            };
            axios
                .post('/api/posters/' + poster.id + '/' + column, params)
                .then((response) => {})
                .catch((e) => {});
        },
        /**
         * Watch the media servers for playback.
         *
         * Plex and Kodi were previously watched over websockets opened straight
         * from the browser, and the Plex socket carried plex_token in its URL.
         * Jellyfin was polled directly with api_key in the query string. All
         * three now go through /api/now-playing/<service>, so the credentials
         * stay on the server.
         */
        startSockets() {
            this.startNowPlayingPolling();
        },
        startNowPlayingPolling() {
            const services = this.enabledMediaServices();

            if (services.length === 0) {
                return;
            }

            console.log('POLLING NOW PLAYING:', services.join(', '));
            this.stopNowPlayingPolling();

            this.nowPlayingInterval = setInterval(() => {
                services.forEach((service) => this.fetchNowPlaying(service));
            }, this.nowPlayingIntervalTime);
        },
        stopNowPlayingPolling() {
            if (this.nowPlayingInterval) {
                clearInterval(this.nowPlayingInterval);
                this.nowPlayingInterval = null;
            }
        },
        controlPlayerState(state) {
            switch (state) {
                case 'playing':
                    console.log('-- STARTED NOW PLAYING');
                    this.nowPlaying = true;
                    break;
                case 'paused':
                case 'stopped':
                    console.log('-- STOPPED NOW PLAYING');
                    this.nowPlaying = false;
                    this.isPlaying = false;
                    this.contentRating = 0;
                    break;
            }
        },
        startTransitionImages() {
            console.log('START TRANSITIONS');
            window.transitionImagesInterval = setInterval(() => {
                this.transitionImages();
            }, this.settings.poster_display_speed);
        },
        stopTransitionImages() {
            console.log('STOP TRANSITIONS');
            clearInterval(window.transitionImagesInterval);
        },
        reload() {
            console.log('--- RELOADING ---');
            this.loadingMessage = 'Re-loading<br />Posters ...';
            this.loading = true;
            this.stopTransitionImages();
            clearInterval(this.recentlyAddedInterval);
            this.stopSettingsInterval();
            this.stopMusic();
            this.socket = null;
            this.videoPlaying = false;
            this.nowPlaying = false;
            this.isPlaying = false;
            setTimeout(() => {
                this.boot();
            }, 2000);
        },
        setLoading(value) {
            this.loading = value;
        },
        processApiEvent(data) {
            if (data.event === 'now-playing') {
                console.log('-- NOW PLAYING EVENT --', data);
                this.servicePlaying = data.mediaSource;
                this.controlPlayerState('playing');
                this.setNowPlaying(data);
            }
            if (data.event === 'stopped') {
                console.log('-- STOPPED EVENT --', data);
                this.controlPlayerState('stopped');
                this.servicePlaying = null;
            }
        },
        setSocket() {
            this.socket = io('http://' + location.hostname + ':3000');

            this.socket.on('AppEventsDmpEvent', (data) => {
                this.processApiEvent(data);
            });

            this.socket.on('receive:command', (data) => {
                switch (data.command) {
                    case 'reload':
                        console.log('-- RELOAD COMMAND --');
                        this.reload();
                        break;
                }
            });
        },
        setNowPlayingPoster(data) {
            this.nowPlayingPoster = data;
        },
        setVideoPlayerRef(data) {
            this.videoPlayer = data;
        },
    },
});
