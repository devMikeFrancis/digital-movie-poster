<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-3">
                        <main-nav />
                    </div>
                    <div class="lg:col-span-9 p-4" style="background-color: #121212">
                        <ul class="tabs">
                            <li>
                                <a
                                    class="active text-sm md:text-md"
                                    href="#general"
                                    @click.prevent="setTab($event)"
                                    >General</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#theme"
                                    class="text-sm md:text-md"
                                    @click.prevent="setTab($event)"
                                    >Theme</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#sources"
                                    class="text-sm md:text-md"
                                    @click.prevent="setTab($event)"
                                    >Poster Sources</a
                                >
                            </li>
                        </ul>
                        <div class="tabs-content">
                            <div id="general" class="tab-content active">
                                <div class="mb-5">
                                    <label for="tmdb-v3" class="text-gray-300 block mb-2 font-bold"
                                        >TMDB Api Key v3</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="tmdb-v3"
                                        aria-describedby="tmdb-v3Help"
                                        v-model="settings.tmdb_api_key_v3"
                                    />
                                    <div id="tmdb-v3Help" class="text-gray-400 text-sm"></div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        for="random"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="random"
                                            aria-describedby="randomHelp"
                                            v-model="settings.random_order"
                                        />
                                        <span class="ml-2">Randomize Poster Order</span>
                                    </label>
                                    <div id="randomHelp" class="text-gray-400 text-sm">
                                        Randomize poster order or display posters in order you
                                        selected.
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
                                    <div
                                        id="coming-soon-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
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
                                        id="now-playing-text"
                                        aria-describedby="now-playing-textHelp"
                                        v-model="settings.now_playing_text"
                                    />
                                    <div
                                        id="now-playing-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <h3 class="text-xl font-bold text-white mb-5">Global Options</h3>

                                <div class="mb-5">
                                    <label
                                        for="show-runtime"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="show-runtime"
                                            v-model="settings.show_runtime"
                                        />
                                        <span class="ml-2">Show Runtime</span>
                                    </label>
                                    <div id="show-runtimeHelp" class="text-gray-400 text-sm">
                                        Displays the movie runtime in the top left corner.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="mpaa-rating"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="mpaa-rating"
                                            aria-describedby="mpaa-ratingHelp"
                                            v-model="settings.show_mpaa_rating"
                                        />
                                        <span class="ml-2">Show Media Rating</span>
                                    </label>
                                    <div id="mpaa-ratingHelp" class="text-gray-400 text-sm">
                                        Shows the movie or TV rating.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="audience-rating"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="audience-rating"
                                            aria-describedby="audience-ratingHelp"
                                            v-model="settings.show_audience_rating"
                                        />
                                        <span class="ml-2">Show Audience Rating</span></label
                                    >
                                    <div id="audience-ratingHelp" class="text-gray-400 text-sm">
                                        Shows the audience rating when a movie is playing.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="theme-music"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="theme-music"
                                            aria-describedby="theme-musicHelp"
                                            v-model="settings.play_theme_music"
                                        />
                                        <span class="ml-2">Play Theme Music</span></label
                                    >
                                    <div id="theme-musicHelp" class="text-gray-400 text-sm">
                                        Play theme music for posters
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="processing-logos"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="processing-logos"
                                            aria-describedby="processing-logosHelp"
                                            v-model="settings.show_processing_logos"
                                        />
                                        <span class="ml-2">Show Processing Logos</span>
                                    </label>
                                    <div id="processing-logosHelp" class="text-gray-400 text-sm">
                                        Shows logos such as Dolby Atmos or Dolby Vision.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="mpaa-limit"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        Movie Rating Display Limit
                                    </label>
                                    <select
                                        class="text-black mb-2"
                                        id="mpaa-limit"
                                        aria-describedby="processing-mpaalimitHelp"
                                        v-model="settings.mpaa_limit"
                                    >
                                        <option value="">None</option>
                                        <option value="G">G</option>
                                        <option value="PG">PG</option>
                                        <option value="PG-13">PG-13</option>
                                        <option value="R">R</option>
                                        <option value="NC-17">NC-17</option>
                                    </select>

                                    <div
                                        id="processing-mpaalimitHelp"
                                        class="text-gray-400 text-sm"
                                    >
                                        Hide any media that is higher than the selected MPAA limit.
                                        Media that is not rated will not be shown.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="tv-limit"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        TV Rating Display Limit
                                    </label>
                                    <select
                                        class="text-black mb-2"
                                        id="tv-limit"
                                        aria-describedby="processing-tvlimitHelp"
                                        v-model="settings.tv_limit"
                                    >
                                        <option value="">None</option>
                                        <option value="TV-Y">TV-Y</option>
                                        <option value="TV-Y7">TV-Y7</option>
                                        <option value="TV-Y7 FV">TV-Y7 FV</option>
                                        <option value="TV-G">TV-G</option>
                                        <option value="TV-PG">TV-PG</option>
                                        <option value="TV-14">TV-14</option>
                                        <option value="TV-MA">TV-MA</option>
                                    </select>

                                    <div id="processing-tvlimitHelp" class="text-gray-400 text-sm">
                                        Hide any media that is higher than the selected TV limit.
                                        Media that is not rated will not be shown.
                                    </div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_dolby_51"
                                        />
                                        <span class="ml-2">Show Dolby Digital 5.1 Logo</span></label
                                    >
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_dolby_atmos_vertical"
                                        />
                                        <span class="ml-2">Show Dolby Atmos Logo</span></label
                                    >
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_dolby_vision_vertical"
                                        />
                                        <span class="ml-2">Show Dolby Vision Logo</span></label
                                    >
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_dts"
                                        />
                                        <span class="ml-2">Show DTS:X Logo</span></label
                                    >
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_imax"
                                        />
                                        <span class="ml-2">Show IMAX Enhanced</span></label
                                    >
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.show_auro_3d"
                                        />
                                        <span class="ml-2">Show Auro 3D Logo</span></label
                                    >
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-1 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.validate_movie_titles"
                                        />
                                        <span class="ml-2">Validate movie titles when syncing</span>
                                    </label>
                                    <div class="text-sm mb-3">
                                        Useful when using multiple sync services. The movie titles
                                        will have to match exactly.
                                    </div>

                                    <label
                                        class="text-gray-300 block mb-1 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.remove_black_bars"
                                        />
                                        <span class="ml-2">Remove side black bars</span>
                                    </label>
                                    <div class="text-sm mb-3">
                                        The small space on each side of the poster. The space is
                                        helpful when framing the TV.
                                    </div>

                                    <label
                                        class="text-gray-300 block mb-3 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="settings.use_global_prologos"
                                        />
                                        <span class="ml-2"
                                            >Use processing logos from global settings</span
                                        >
                                    </label>
                                    <label
                                        class="text-gray-300 block mb-3 font-bold flex items-center"
                                        ><input
                                            class="text-black"
                                            type="checkbox"
                                            v-model="
                                                settings.use_global_prologos_if_no_poster_prologos
                                            "
                                        />
                                        <span class="ml-2"
                                            >Use global processing logos if no poster logos</span
                                        >
                                    </label>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        for="cec-controls"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="cec-controls"
                                            aria-describedby="cec-controlsHelp"
                                            v-model="settings.use_cec_power"
                                        />
                                        <span class="ml-2">Use HDMI CEC Controls</span>
                                    </label>
                                    <div id="cec-controlsHelp" class="text-gray-400 text-sm">
                                        Allow the application to turn on/off your display during
                                        certain time noted below.
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
                                        for="end-power-time"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Display End Time</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="end-power-time"
                                        aria-describedby="end-power-timeHelp"
                                        v-model="settings.end_power_time"
                                    />
                                    <div id="end-power-timeHelp" class="text-gray-400 text-sm">
                                        The end time you want you display to be off. HH:MM:SS
                                    </div>
                                </div>
                            </div>
                            <div id="theme" class="tab-content">
                                <div class="mb-5">
                                    <label
                                        for="poster-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Poster Background Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="poster-bg-color"
                                        aria-describedby="poster-bg-color-textHelp"
                                        v-model="settings.poster_bg_color"
                                    />
                                    <div
                                        id="header-bg-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="header-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Top Background Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-bg-color"
                                        aria-describedby="header-bg-color-textHelp"
                                        v-model="settings.header_bg_color"
                                    />
                                    <div
                                        id="header-bg-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="header-text-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Top Text Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-text-color"
                                        aria-describedby="header-text-color-textHelp"
                                        v-model="settings.header_text_color"
                                    />
                                    <div
                                        id="header-text-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="show-header-border"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="show-header-border"
                                            v-model="settings.show_header_border"
                                        />
                                        <span class="ml-2">Show Top Border</span>
                                    </label>
                                    <div id="show-header-border-Help" class="text-gray-400 text-sm">
                                        Displays thin border around "Coming Soon/Now Playing" text.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="header-border-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Top Border Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-border-color"
                                        aria-describedby="header-border-color-textHelp"
                                        v-model="settings.header_border_color"
                                    />
                                    <div
                                        id="header-border-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="header-font"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Coming Soon/Now Playing Font</label
                                    >

                                    <select
                                        class="w-full"
                                        id="header-font"
                                        aria-describedby="header-font-textHelp"
                                        v-model="settings.header_font"
                                    >
                                        <option value="default">Default</option>
                                        <option value="riemann-theater">Riemann Theater</option>
                                        <option value="great-attraction">Great Attraction</option>
                                        <option value="midnight-champion">Midnight Champion</option>
                                        <option value="emerald">Emerald</option>
                                        <option value="airstrike">Airstrike</option>
                                        <option value="space-ranger">Space Ranger</option>
                                        <option value="feast-flesh">Feast of Flesh</option>
                                        <option value="camp-blood">Camp Blood</option>
                                        <option value="friday13">Friday 13th</option>
                                    </select>
                                    <div
                                        id="header-font-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="header-font-size"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Coming Soon/Now Playing Font Size</label
                                    >

                                    <select
                                        class="w-full"
                                        id="header-font-size"
                                        aria-describedby="header-font-size-textHelp"
                                        v-model="settings.header_font_size"
                                    >
                                        <option value="xsmall">X-Small</option>
                                        <option value="small">Small</option>
                                        <option value="normal">Normal</option>
                                        <option value="large">Large</option>
                                        <option value="xlarge">X-Large</option>
                                    </select>
                                    <div
                                        id="header-font-size-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="footer-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Bottom Background Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="footer-bg-color"
                                        aria-describedby="footer-bg-color-textHelp"
                                        v-model="settings.footer_bg_color"
                                    />
                                    <div
                                        id="footer-bg-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="footer-text-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Bottom Text Color</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="footer-text-color"
                                        aria-describedby="footer-text-color-textHelp"
                                        v-model="settings.footer_text_color"
                                    />
                                    <div
                                        id="footer-text-color-textHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>
                            </div>
                            <div id="sources" class="tab-content">
                                <div class="mb-5">
                                    <label
                                        for="plex-service"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="plex-service"
                                            aria-describedby="plex-serviceHelp"
                                            v-model="settings.plex_service"
                                        />
                                        <span class="ml-2">Enable Plex Service</span></label
                                    >
                                    <div id="plex-serviceHelp" class="text-gray-400 text-sm">
                                        Use Plex media server for posters.
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="ip-address"
                                        class="text-gray-300 block mb-2 font-bold"
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
                                    <label
                                        for="plex-token"
                                        class="text-gray-300 block mb-2 font-bold"
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

                                <div class="mb-5">
                                    <label
                                        for="sync-plex-movies"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="sync-plex-movies"
                                            aria-describedby="syncplexmoviesHelp"
                                            v-model="settings.plex_sync_movies"
                                        />
                                        <span class="ml-2">Sync Plex Movies</span>
                                    </label>
                                    <div
                                        id="syncplexmoviesHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="sync-plex-tv"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="sync-plex-tv"
                                            aria-describedby="syncplextvHelp"
                                            v-model="settings.plex_sync_tv"
                                        />
                                        <span class="ml-2">Sync Plex TV Shows</span>
                                    </label>
                                    <div id="syncplextvHelp" class="text-gray-400 text-sm"></div>
                                </div>

                                <div v-if="settings.plex_service">
                                    <button
                                        class="btn mb-4 hover:text-white"
                                        @click.prevent="getServiceSections('plex')"
                                    >
                                        Refresh Plex Media Libraries
                                        <small>(Save Plex Credentials first)</small>
                                    </button>

                                    <div class="mb-5" v-if="settings.plex_sync_movies">
                                        <label
                                            for="plex-movie-sections"
                                            class="
                                                text-gray-300
                                                block
                                                mb-2
                                                font-bold
                                                flex
                                                items-center
                                            "
                                            >Plex Movie Libraries</label
                                        >
                                        <select id="plex-movie-sections" v-model="plexMovieSection">
                                            <option value=""></option>
                                            <option
                                                v-for="(movieSection, mIndex) in plexMovieSections"
                                                :value="movieSection.key"
                                                :key="'msection-' + mIndex"
                                            >
                                                {{ movieSection.title }}
                                            </option>
                                        </select>
                                        <button
                                            class="
                                                text-black text-sm
                                                bg-white
                                                border-2 border-gray-500
                                                px-3
                                                py-2
                                                ml-3
                                                rounded-none
                                                hover:bg-gray-700
                                                hover:text-white
                                            "
                                            @click.prevent="addMovieSyncLibrary('plex')"
                                        >
                                            &plus; Sync Library
                                        </button>
                                    </div>

                                    <div class="mb-5" v-if="settings.plex_sync_movies">
                                        <ul
                                            class="bg-gray-700 px-3 py-2 flex"
                                            v-if="settings.plex_movie_sections"
                                        >
                                            <li v-if="settings.plex_movie_sections.length === 0">
                                                <span class="text-white"
                                                    >No Movie libraries added yet.</span
                                                >
                                            </li>
                                            <li
                                                class="mr-3 bg-white px-2"
                                                v-for="(
                                                    pmSection, pmIndex
                                                ) in settings.plex_movie_sections"
                                                :key="'pmindex-' + pmIndex"
                                            >
                                                <span class="text-black">{{
                                                    getMovieLibraryName('plex', pmSection)
                                                }}</span>
                                                <a
                                                    href="#"
                                                    role="button"
                                                    @click.prevent="
                                                        removeMovieSyncLibrary('plex', pmSection)
                                                    "
                                                    ><span class="ml-2 text-xl text-red-700"
                                                        >&times;</span
                                                    ></a
                                                >
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mb-5" v-if="settings.plex_sync_tv">
                                        <label
                                            for="plex-tv-sections"
                                            class="
                                                text-gray-300
                                                block
                                                mb-2
                                                font-bold
                                                flex
                                                items-center
                                            "
                                            >Plex TV Libraries</label
                                        >
                                        <select id="plex-tv-sections" v-model="plexTvSection">
                                            <option value=""></option>
                                            <option
                                                v-for="(tvSection, tIndex) in plexTvSections"
                                                :value="tvSection.key"
                                                :key="'tsection-' + tIndex"
                                            >
                                                {{ tvSection.title }}
                                            </option>
                                        </select>
                                        <button
                                            class="
                                                text-black text-sm
                                                bg-white
                                                border-2 border-gray-500
                                                px-3
                                                py-2
                                                ml-3
                                                rounded-none
                                                hover:bg-gray-700
                                                hover:text-white
                                            "
                                            @click.prevent="addTvSyncLibrary('plex')"
                                        >
                                            &plus; Sync Library
                                        </button>
                                    </div>

                                    <div class="mb-5" v-if="settings.plex_sync_tv">
                                        <ul
                                            class="bg-gray-700 px-3 py-2 flex"
                                            v-if="settings.plex_tv_sections"
                                        >
                                            <li v-if="settings.plex_tv_sections.length === 0">
                                                <span class="text-white"
                                                    >No TV libraries added yet.</span
                                                >
                                            </li>
                                            <li
                                                class="mr-3 bg-white px-2"
                                                v-for="(
                                                    tvSection, tvIndex
                                                ) in settings.plex_tv_sections"
                                                :key="'pmindex-' + tvIndex"
                                            >
                                                <span class="text-black">{{
                                                    getTvLibraryName('plex', tvSection)
                                                }}</span>
                                                <a
                                                    href="#"
                                                    role="button"
                                                    @click.prevent="
                                                        removeTvSyncLibrary('plex', tvSection)
                                                    "
                                                    ><span class="ml-2 text-xl text-red-700"
                                                        >&times;</span
                                                    ></a
                                                >
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        for="jellyfin-service"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="jellyfin-service"
                                            aria-describedby="jellyfin-serviceHelp"
                                            v-model="settings.jellyfin_service"
                                        />
                                        <span class="ml-2">Enable Jellyfin Service</span></label
                                    >
                                    <div id="jellyfin-serviceHelp" class="text-gray-400 text-sm">
                                        Use Jellyfin media server for posters.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="jellyfin-ip-address"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Jellyfin Server IP Address</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="jellyfin-ip-address"
                                        aria-describedby="jellyfinIpAddressHelp"
                                        v-model="settings.jellyfin_ip_address"
                                    />
                                    <div id="jellyfinIpAddressHelp" class="text-gray-400 text-sm">
                                        The IP address of your Jellyfin server.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="jellyfin-token"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Jellyfin API Token</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="jellyfin-token"
                                        aria-describedby="jellyfintokenHelp"
                                        v-model="settings.jellyfin_token"
                                    />
                                    <div id="jellyfintokenHelp" class="text-gray-400 text-sm"></div>
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        for="jellyfin-service"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="kodi-service"
                                            aria-describedby="kodi-serviceHelp"
                                            v-model="settings.kodi_service"
                                        />
                                        <span class="ml-2">Enable Kodi Service</span></label
                                    >
                                    <div id="kodi-serviceHelp" class="text-gray-400 text-sm">
                                        Use Kodi media server for posters.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label for="kodi-url" class="text-gray-300 block mb-2 font-bold"
                                        >Kodi Server IP Address</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="kodi-url"
                                        aria-describedby="kodiurlHelp"
                                        v-model="settings.kodi_url"
                                    />
                                    <div id="kodiurlHelp" class="text-gray-400 text-sm">
                                        The IP address of your Kodi server.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="kodi-port"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Kodi port</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="kodi-port"
                                        aria-describedby="kodiportHelp"
                                        v-model="settings.kodi_port"
                                    />
                                    <div id="kodiportHelp" class="text-gray-400 text-sm">
                                        The port of your Kodi server.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="kodi-user"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Kodi Username (optional if set)</label
                                    >
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="kodi-user"
                                        aria-describedby="kodiuserHelp"
                                        v-model="settings.kodi_username"
                                    />
                                    <div id="kodiuserHelp" class="text-gray-400 text-sm"></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="kodi-pass"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Kodi Password (optional if set)</label
                                    >
                                    <input
                                        type="password"
                                        class="text-black w-full"
                                        id="kodi-pass"
                                        aria-describedby="kodipassHelp"
                                        v-model="settings.kodi_password"
                                    />
                                    <div id="kodipassHelp" class="text-gray-400 text-sm"></div>
                                </div>
                            </div>
                        </div>
                        <!-- / .tabs-content -->

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
                            <div v-for="(err, eIndex) in errors" :key="'err-' + eIndex">
                                {{ err }}
                            </div>
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
                                        rounded-sm
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
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import MainNav from '@/partials/MainNav.vue';

export default {
    data: function () {
        return {
            loading: false,
            settingsMessage: '',
            errors: [],
            settings: {
                plex_token: '',
                plex_ip_address: '',
                jellyfin_token: '',
                jellyfin_ip_address: '',
                transition_type: 'fade',
            },
            plexSections: [],
            plexTvSection: '',
            plexMovieSection: '',
            updateBtn: 'Update DMP',
            updateOutput: '',
            socket: '',
        };
    },
    components: { MainNav },
    watch: {},
    computed: {
        plexTvSections() {
            return this.plexSections.filter((item) => {
                return item.type === 'show';
            });
        },
        plexMovieSections() {
            return this.plexSections.filter((item) => {
                return item.type === 'movie';
            });
        },
    },
    methods: {
        setTab(event) {
            let tab = event.target.getAttribute('href');
            let $tabItems = document.querySelectorAll('.tabs a');
            let $tabContents = document.querySelectorAll('.tab-content');
            let $activeTab = event.target;
            let $activeTabContent = document.querySelector(tab);

            $tabContents.forEach((el) => {
                el.classList.remove('active');
            });
            $tabItems.forEach((el) => {
                el.classList.remove('active');
            });

            $activeTab.classList.add('active');
            $activeTabContent.classList.add('active');
        },
        getSettings() {
            axios
                .get('/api/settings')
                .then((response) => {
                    this.settings = response.data;
                    if (this.settings.plex_service) {
                        this.getServiceSections('plex');
                    }
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        saveSettings() {
            this.settingsMessage = '';
            this.errors = [];
            this.settings._method = 'put';

            axios
                .post('/api/settings', this.settings)
                .then((response) => {
                    this.settingsMessage = 'Settings saved.';
                    setTimeout(() => {
                        this.settingsMessage = null;
                    }, 2500);
                })
                .catch((e) => {
                    this.settingsMessage = e.message;
                    let errors = e.response.data.errors;
                    if (Object.keys(errors).length !== 0) {
                        for (var prop in errors) {
                            if (errors[prop] instanceof Array) {
                                errors[prop].forEach((err) => {
                                    this.errors.push(err);
                                });
                            }
                        }
                    }
                });
        },
        getMovieLibraryName(service, key) {
            if (service === 'plex') {
                let obj = this.plexMovieSections.find((item) => {
                    return item.key === key;
                });
                return obj ? obj.title : '';
            }
        },
        getTvLibraryName(service, key) {
            if (service === 'plex') {
                let obj = this.plexTvSections.find((item) => {
                    return item.key === key;
                });
                return obj ? obj.title : '';
            }
        },
        addMovieSyncLibrary(service) {
            if (service === 'plex') {
                if (this.plexMovieSection) {
                    if (!this.settings.plex_movie_sections) {
                        this.settings.plex_movie_sections = [];
                    }
                    if (!this.settings.plex_movie_sections.includes(this.plexMovieSection)) {
                        this.settings.plex_movie_sections.push(this.plexMovieSection);
                    }
                }
            }
        },
        addTvSyncLibrary(service) {
            if (service === 'plex') {
                if (this.plexTvSection) {
                    if (!this.settings.plex_tv_sections) {
                        this.settings.plex_tv_sections = [];
                    }
                    if (!this.settings.plex_tv_sections.includes(this.plexTvSection)) {
                        this.settings.plex_tv_sections.push(this.plexTvSection);
                    }
                }
            }
        },
        removeMovieSyncLibrary(service, item) {
            if (service === 'plex') {
                this.settings.plex_movie_sections.splice(
                    this.settings.plex_movie_sections.indexOf(item),
                    1
                );
            }
        },
        removeTvSyncLibrary(service, item) {
            if (service === 'plex') {
                this.settings.plex_tv_sections.splice(
                    this.settings.plex_tv_sections.indexOf(item),
                    1
                );
            }
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
        getServiceSections(service) {
            axios
                .get('/api/service-sections/' + service)
                .then((response) => {
                    this.plexSections = response.data;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
    },
    created() {},
    mounted() {
        this.getSettings();
        if (typeof io !== 'undefined') {
            this.socket = io('http://' + location.hostname + ':3000');
        }
    },
};
</script>

<style scoped lang="scss">
input[type='text'],
input[type='number'] {
    height: 42px;
    border-radius: 2px;
}

.tabs {
    display: flex;

    li {
        margin-right: 6px;

        &:last-child {
            margin-right: 0;
        }

        a {
            display: block;
            padding: 8px 0;
            min-width: 112px;
            text-align: center;
            color: #888;
            background-color: #333;

            &:hover {
                background-color: #777;
                color: #ccc;
                transition: background-color 0.25s ease;
            }

            &.active {
                color: #fff;
                background-color: #555;

                &:hover {
                    background-color: #777;
                    transition: background-color 0.25s ease;
                }
            }
        }
    }
}
.tabs-content {
    margin-bottom: 24px;
    position: relative;

    .tab-content {
        display: none;
        padding: 24px;

        &.active {
            display: block;
            border-top: 1px solid #555;
        }
    }
}
</style>
