<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-2">
                        <ul
                            class="block text-gray-300 p-4 sticky top-0 mb-5"
                            style="background-color: #121212"
                        >
                            <li class="mb-3">
                                <router-link class="hover:text-gray-500" to="/"
                                    >&larr; Back to DMP</router-link
                                >
                            </li>
                            <li class="mb-3">
                                <router-link class="hover:text-gray-500" to="/posters"
                                    >Posters</router-link
                                >
                            </li>
                            <li class="mb-3">
                                <router-link class="hover:text-gray-500" to="/voting"
                                    >Voting</router-link
                                >
                            </li>
                            <li>
                                <router-link class="hover:text-gray-500 active" to="/settings"
                                    >Settings</router-link
                                >
                            </li>
                        </ul>
                        <button
                            class="
                                w-full
                                text-white text-center
                                py-2
                                px-1
                                rounded-md
                                bg-gray-700
                                hover:bg-gray-500
                            "
                            type="button"
                            @click.prevent="reloadPosters()"
                        >
                            Refresh Movie Posters
                        </button>
                    </div>
                    <div class="lg:col-span-6 p-4" style="background-color: #121212">
                        <form>
                            <div class="mb-5">
                                <label
                                    for="plex-service"
                                    class="text-gray-300 block mb-2 font-bold"
                                >
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="plex-service"
                                        aria-describedby="plex-serviceHelp"
                                        v-model="settings.plex_service"
                                    />
                                    Enable Plex Service</label
                                >
                                <div id="plex-serviceHelp" class="text-gray-400 text-sm">
                                    Use Plex media server for posters.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="ip-address" class="text-gray-300 block mb-2 font-bold"
                                    >Plex Server IP Address</label
                                >
                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="ip-address"
                                    aria-describedby="ipAddressHelp"
                                    v-model="settings.plex_ip_address"
                                />
                                <div id="ipAddressHelp" class="text-gray-400 text-sm">
                                    The IP address of your Plex server.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="plex-token" class="text-gray-300 block mb-2 font-bold"
                                    >Plex Token</label
                                >
                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="plex-token"
                                    aria-describedby="tokenHelp"
                                    v-model="settings.plex_token"
                                />
                                <div id="tokenHelp" class="text-gray-400 text-sm">
                                    You can find your Plex token
                                    <a
                                        href="https://support.plex.tv/articles/204059436-finding-an-authentication-token-x-plex-token/"
                                        target="_blank"
                                        class="underline"
                                        >here</a
                                    >.
                                </div>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div class="mb-5">
                                <label for="random" class="text-gray-300 block mb-2 font-bold">
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="random"
                                        aria-describedby="randomHelp"
                                        v-model="settings.random_order"
                                    />
                                    Randomize Poster Order
                                </label>
                                <div id="randomHelp" class="text-gray-400 text-sm">
                                    Randomize poster order or display posters in order you selected.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="type" class="text-gray-300 block mb-2 font-bold">
                                    Transition Type
                                </label>
                                <select
                                    class="text-black"
                                    id="type"
                                    aria-describedby="typeHelp"
                                    v-model="settings.transition_type"
                                >
                                    <option value="fade">Fade</option>
                                    <option value="vertical">Vertical</option>
                                </select>

                                <div id="typeHelp" class="text-gray-400 text-sm">
                                    Fade in/out or Vertical slide transition.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="display-speed"
                                    class="text-gray-300 block mb-2 font-bold"
                                    >Poster Display Speed</label
                                >

                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="display-speed"
                                    aria-describedby="display-speedHelp"
                                    v-model="settings.poster_display_speed"
                                />
                                <div id="display-speedHelp" class="text-gray-400 text-sm">
                                    Time between each poster. In ms. 15000 = 15 seconds.
                                </div>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div class="mb-5">
                                <label
                                    for="coming-soon-text"
                                    class="text-gray-300 block mb-2 font-bold"
                                    >Coming Soon Text</label
                                >

                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="coming-soon-text"
                                    aria-describedby="coming-soon-textHelp"
                                    v-model="settings.coming_soon_text"
                                />
                                <div id="coming-soon-textHelp" class="text-gray-400 text-sm"></div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="now-playing-text"
                                    class="text-gray-300 block mb-2 font-bold"
                                    >Now Playing Text</label
                                >

                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="coming-soon-text"
                                    aria-describedby="now-playing-textHelp"
                                    v-model="settings.now_playing_text"
                                />
                                <div id="now-playing-textHelp" class="text-gray-400 text-sm"></div>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <h3 class="text-xl font-bold text-white mb-5">Global Options</h3>

                            <div class="mb-5">
                                <label
                                    for="show-runtime"
                                    class="text-gray-300 block mb-2 font-bold"
                                >
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="show-runtime"
                                        v-model="settings.show_runtime"
                                    />
                                    Show Runtime
                                </label>
                                <div id="show-runtimeHelp" class="text-gray-400 text-sm">
                                    Displays the movie runtime in the top left corner.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="mpaa-rating" class="text-gray-300 block mb-2 font-bold">
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="mpaa-rating"
                                        aria-describedby="mpaa-ratingHelp"
                                        v-model="settings.show_mpaa_rating"
                                    />
                                    Show MPAA Rating
                                </label>
                                <div id="mpaa-ratingHelp" class="text-gray-400 text-sm">
                                    Shows the MPAA rating when a movie is playing.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="audience-rating"
                                    class="text-gray-300 block mb-2 font-bold"
                                >
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="audience-rating"
                                        aria-describedby="audience-ratingHelp"
                                        v-model="settings.show_audience_rating"
                                    />
                                    Show Audience Rating</label
                                >
                                <div id="audience-ratingHelp" class="text-gray-400 text-sm">
                                    Shows the audience rating when a movie is playing.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="theme-music" class="text-gray-300 block mb-2 font-bold">
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="theme-music"
                                        aria-describedby="theme-musicHelp"
                                        v-model="settings.play_theme_music"
                                    />
                                    Play Theme Music</label
                                >
                                <div id="theme-musicHelp" class="text-gray-400 text-sm">
                                    Play theme music for posters
                                </div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="processing-logos"
                                    class="text-gray-300 block mb-2 font-bold"
                                >
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="processing-logos"
                                        aria-describedby="processing-logosHelp"
                                        v-model="settings.show_processing_logos"
                                    />
                                    Show Processing Logos
                                </label>
                                <div id="processing-logosHelp" class="text-gray-400 text-sm">
                                    Shows logos such as Dolby Atmos or Dolby Vision.
                                </div>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div class="mb-5">
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_dolby_51"
                                    />
                                    Show Dolby Digital 5.1 Logo</label
                                >
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_dolby_atmos_vertical"
                                    />
                                    Show Dolby Atmos Logo</label
                                >
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_dolby_vision_vertical"
                                    />
                                    Show Dolby Vision Logo</label
                                >
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_dts"
                                    />
                                    Show DTS:X Logo</label
                                >
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_imax"
                                    />
                                    Show IMAX Enhanced</label
                                >
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.show_auro_3d"
                                    />
                                    Show Auro 3D Logo</label
                                >
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div class="mb-5">
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.use_global_prologos"
                                    />
                                    Always use enabled processing logos from settings.
                                </label>
                                <label class="text-gray-300 block mb-2 font-bold"
                                    ><input
                                        class="text-black"
                                        type="checkbox"
                                        v-model="settings.use_global_prologos_if_no_poster_prologos"
                                    />
                                    Use settings processing logos only if poster does not have any
                                    enabled.
                                </label>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div class="mb-5">
                                <label
                                    for="cec-controls"
                                    class="text-gray-300 block mb-2 font-bold"
                                >
                                    <input
                                        type="checkbox"
                                        class="text-black"
                                        id="cec-controls"
                                        aria-describedby="cec-controlsHelp"
                                        v-model="settings.use_cec_power"
                                    />
                                    Use HDMI CEC Controls
                                </label>
                                <div id="cec-controlsHelp" class="text-gray-400 text-sm">
                                    Allow the application to turn on/off your display during certain
                                    time noted below.
                                </div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="start-power-time"
                                    class="text-gray-300 block mb-2 font-bold"
                                    >Display Start Time</label
                                >

                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="start-power-time"
                                    aria-describedby="start-power-timeHelp"
                                    v-model="settings.start_power_time"
                                />
                                <div id="start-power-timeHelp" class="text-gray-400 text-sm">
                                    The start time you want you display to be on. HH:MM:SS
                                </div>
                            </div>

                            <div class="mb-5">
                                <label
                                    for="start-power-time"
                                    class="text-gray-300 block mb-2 font-bold"
                                    >Display Start Time</label
                                >

                                <input
                                    type="text"
                                    class="text-black w-full"
                                    id="start-power-time"
                                    aria-describedby="end-power-timeHelp"
                                    v-model="settings.end_power_time"
                                />
                                <div id="end-power-timeHelp" class="text-gray-400 text-sm">
                                    The end time you want you display to be off. HH:MM:SS
                                </div>
                            </div>

                            <hr class="mt-3 mb-7 border-gray-700" />

                            <div
                                class="
                                    bg-red-100
                                    border border-red-400
                                    text-red-700
                                    px-4
                                    py-3
                                    rounded
                                    relative
                                    mb-3
                                "
                                role="alert"
                                v-if="settingsMessage"
                                v-cloak
                            >
                                {{ settingsMessage }}
                            </div>
                            <div class="grid grid-cols-12">
                                <div class="col-span-6">
                                    <button
                                        type="submit"
                                        class="
                                            btn
                                            text-black
                                            bg-gray-300
                                            text-md
                                            px-3
                                            py-1
                                            hover:bg-gray-100
                                        "
                                        @click.prevent="saveSettings"
                                    >
                                        Save Settings
                                    </button>
                                </div>
                                <div class="col-span-6 text-right">
                                    <a
                                        href="#"
                                        role="button"
                                        class="text-white"
                                        @click.prevent="updateApplication"
                                        >{{ updateBtn }}</a
                                    >
                                </div>
                            </div>
                        </form>
                        <div
                            class="update-output text-white p-5"
                            style="white-space: pre"
                            v-html="updateOutput"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            loading: false,
            settingsMessage: '',
            settings: {
                plex_token: '',
                plex_ip_address: '',
                transition_type: 'fade',
            },
            updateBtn: 'Update DMP',
            updateOutput: '',
            socket: '',
        };
    },
    components: {},
    watch: {},
    methods: {
        getSettings() {
            axios
                .get('/api/settings')
                .then((response) => {
                    this.settings = response.data;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        saveSettings() {
            this.settingsMessage = '';
            this.settings._method = 'put';

            axios
                .post('/api/settings', this.settings)
                .then((response) => {
                    this.settingsMessage = 'Settings saved.';
                })
                .catch((e) => {
                    this.settingsMessage = e.message;
                });
        },
        updateApplication() {
            this.updateOutput = '';
            this.updateBtn = 'Updating ...';
            axios
                .get('/api/update-application')
                .then((response) => {
                    this.updateOutput = response.data.output;
                    this.updateBtn = 'Update DMP';
                })
                .catch((e) => {
                    console.log(e.message);
                    this.updateBtn = 'Update DMP';
                });
        },
        reloadPosters() {
            this.socket.emit('dispatch:command', { command: 'reload' });
        },
    },
    created() {},
    mounted() {
        this.getSettings();
        if (typeof io !== 'undefined') {
            this.socket = io('http://movieposter.local:3000');
        }
    },
};
</script>

<style scoped lang="scss">
input[type='text'],
input[type='number'] {
    height: 32px;
    border-radius: 4px;
}
</style>
