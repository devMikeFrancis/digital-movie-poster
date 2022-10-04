import Api from '@/services/api';
import { defineStore } from 'pinia';

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
        jellyfinDevicePlaying: null,
        servicePlaying: null,
        canRefreshTransitionTime: false,
        transitionImagesInterval: null,
        contentRating: '',
        mpaaRating: '',
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
                this.controlTV('on');
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
            let poster = '';
            if (this.settings.random_order) {
                poster = this.mediaPosters[this.getRandomPoster()];
            } else {
                poster = this.mediaPosters[0];
            }

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
        startSyncPosters() {
            this.recentlyAddedInterval = setInterval(() => {
                this.cachePosters();
            }, 60000 * 60 * 60 * 1000 * 4); // Every 4 hours
        },
        withinMpaaLimit(rating) {
            let mpaaLimit = this.settings.mpaa_limit;
            if (!mpaaLimit) {
                return true;
            }
            if (mpaaLimit === 'G') {
                return poster.mpaa_rating === 'G';
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
            return false;
        },
        withinTvLimit(rating) {
            let mpaaLimit = this.settings.tv_limit;
            if (!mpaaLimit) {
                return true;
            }
            if (mpaaLimit === 'TV-Y') {
                return poster.mpaa_rating === 'TV-Y';
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
            return false;
        },
        getNowPlaying() {
            console.log('GET NOW PLAYING');
            if (this.settings.plex_service && this.servicePlaying === 'plex') {
                this.plexNowPlaying();
            }
            if (this.settings.jellyfin_service && this.servicePlaying === 'jellyfin') {
                this.jellyfinNowPlaying();
            }
            if (this.settings.kodi_service && this.servicePlaying === 'kodi') {
                this.kodiNowPlaying();
            }
        },
        plexNowPlaying() {
            Api.apiCallPlex('/status/sessions/')
                .then((response) => {
                    const size = response.data.MediaContainer.size;
                    if (size > 0) {
                        let data = response.data.MediaContainer.Metadata[0];
                        let playing = {
                            contentRating: 0,
                            rating: 0,
                            duration: null,
                            poster: '',
                        };

                        playing.poster =
                            'http://' +
                            this.settings.plex_ip_address +
                            ':32400' +
                            response.data.MediaContainer.Metadata[0].thumb +
                            '?X-Plex-Token=' +
                            this.settings.plex_token;

                        playing.contentRating = data.contentRating;

                        if (data.audienceRating) {
                            playing.audienceRating = data.audienceRating;
                        }

                        if (data.duration) {
                            playing.duration = data.duration / 1000 / 60;
                        }

                        this.setNowPlaying(playing);
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        jellyfinNowPlaying() {
            if (this.jellyfinDevicePlaying) {
                let playing = {
                    contentRating: this.jellyfinDevicePlaying.NowPlayingItem.OfficialRating,
                    audienceRating: this.jellyfinDevicePlaying.NowPlayingItem.CommunityRating,
                    duration:
                        this.jellyfinDevicePlaying.NowPlayingItem.RunTimeTicks / 10000 / 1000 / 60,
                    poster:
                        'http://' +
                        this.settings.jellyfin_ip_address +
                        ':8096/Items/' +
                        this.jellyfinDevicePlaying.NowPlayingItem.Id +
                        '/Images/Primary',
                };

                this.setNowPlaying(playing);
            }
        },
        kodiNowPlaying() {
            axios
                .get('/api/kodi-now-playing')
                .then((response) => {
                    let playing = {
                        poster: decodeURIComponent(
                            response.data[1].result.item.art.poster
                                .replace('image://', '')
                                .slice(0, -1)
                        ),
                        contentRating: response.data[0].result.item.mpaa.replace('Rated ', ''),
                        audienceRating: response.data[0].result.item.rating,
                        duration: response.data[0].result.item.runtime / 60,
                    };
                    this.setNowPlaying(playing);
                })
                .catch(() => {});
        },
        setNowPlaying(data) {
            let withinMpaaLimit = this.withinMpaaLimit(data.contentRating);
            if (withinMpaaLimit) {
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
        getRandomPoster() {
            return Math.floor(Math.random() * this.moviePosters.length);
        },
        getInSequencePoster() {
            console.log('GET NEXT POSTER');
            const len = this.mediaPosters.length;
            const currIndex = this.mediaPosters.findIndex((poster) => poster.show === true);
            let poster,
                pastPoster,
                activeIndex = 0;

            if (this.settings.random_order) {
                activeIndex = this.getRandomPoster();
            } else {
                activeIndex = currIndex + 1 === len ? 0 : currIndex + 1;
            }

            if (len === 1) {
                poster = this.mediaPosters[0];
                pastPoster = Object.assign(this.mediaPosters[0], {});
                poster.show = true;
            } else {
                poster = this.mediaPosters[activeIndex];
                pastPoster = this.mediaPosters[currIndex];
                if (poster) {
                    poster.show = true;
                }
                if (pastPoster) {
                    pastPoster.show = false;
                }
            }

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
        startSockets() {
            console.log('STARTING SOCKETS');
            if (this.settings.plex_service) {
                this.plexSocket();
            }
            if (this.settings.jellyfin_service) {
                this.jellyfinSocket();
            }
            if (this.settings.kodi_service) {
                this.kodiSocket();
            }
        },
        plexSocket() {
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
                let state;
                console.log('PLEX ACTION: ', action);
                if (action === 'playing') {
                    state = data.NotificationContainer.PlaySessionStateNotification[0].state;
                    console.log('PLEX STATE: ', state);
                    // Make status session call to check if its a movie
                    if (!this.checkedPlexMediaType) {
                        console.log('PLEX CHECK SESSION TYPE');
                        Api.apiCallPlex('/status/sessions/')
                            .then((response) => {
                                const size = response.data.MediaContainer.size;
                                if (size > 0) {
                                    let data = response.data.MediaContainer.Metadata[0];
                                    this.checkedPlexMediaType = true;
                                    if (data.type === 'movie') {
                                        this.servicePlaying = 'plex';
                                        this.controlPlayerState(state);
                                    }
                                }
                            })
                            .catch(() => {});
                    }
                }

                if (state === 'stopped' && this.servicePlaying === 'plex' && this.nowPlaying) {
                    this.checkedPlexMediaType = false;
                    this.controlPlayerState(state);
                }
            });
        },
        kodiSocket() {
            const socket = new WebSocket('ws://' + this.settings.kodi_url + ':9090');

            socket.addEventListener('open', () => {});

            socket.addEventListener('message', (event) => {
                const data = JSON.parse(event.data);
                if (data.method === 'Player.OnPlay' && data.params.data.item.type === 'movie') {
                    this.servicePlaying = 'kodi';
                    this.controlPlayerState('playing');
                }

                if (data.method === 'Player.OnStop' && data.params.data.item.type === 'movie') {
                    this.servicePlaying = null;
                    this.controlPlayerState('stopped');
                }
            });
        },
        jellyfinSocket() {
            // Jellyfin - we have to poll. Does not have socket for now playing
            setInterval(() => {
                if (this.settings.jellyfin_service) {
                    axios
                        .get(
                            'http://' +
                                this.settings.jellyfin_ip_address +
                                ':8096/Sessions?api_key=' +
                                this.settings.jellyfin_token
                        )
                        .then((response) => {
                            let devices = response.data;
                            this.jellyfinDevicePlaying = devices.find((device) => {
                                if (
                                    device.hasOwnProperty('NowPlayingItem') &&
                                    device.NowPlayingItem.Type === 'Movie'
                                ) {
                                    return device;
                                }
                            });

                            if (this.jellyfinDevicePlaying) {
                                this.servicePlaying = 'jellyfin';
                                this.controlPlayerState('playing');
                            } else {
                                this.servicePlaying = null;
                                this.controlPlayerState('stopped');
                            }
                        })
                        .catch(() => {
                            this.jellyfinDevicePlaying = null;
                            this.servicePlaying = null;
                            this.controlPlayerState('stopped');
                        });
                }
            }, 7000);
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
                    break;
            }
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
            this.stopMusic();
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
        setSocket() {
            this.socket = io('http://' + location.hostname + ':3000');
            // location.host || ' + import.meta.env.VITE_BASE_URL + '
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
