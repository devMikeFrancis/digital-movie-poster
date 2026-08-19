<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-3">
                        <main-nav />
                    </div>
                    <div class="lg:col-span-9 p-4" style="background-color: #121212">
                        <SettingsBar
                            :tabs="tabs"
                            :model-value="tab"
                            :status-text="statusText"
                            :status-class="statusClass"
                            :saving="saving"
                            :unsaved-changes="unsavedChanges"
                            @update:model-value="setTab"
                            @save="saveSettings"
                        />

                        <UnsavedChangesModal
                            :open="!!pendingLeave"
                            :saving="saving"
                            @stay="stayOnPage"
                            @discard="leaveWithoutSaving"
                            @save="saveThenLeave"
                        />

                        <div
                            v-if="errors.length || saveFailed"
                            class="bg-red-900 text-white px-4 py-3 rounded relative mb-4"
                            role="alert"
                            v-cloak
                        >
                            <p class="font-bold mb-1">{{ errorHeading }}</p>
                            <div v-for="(err, eIndex) in errors" :key="'err-' + eIndex">
                                {{ err }}
                            </div>
                        </div>

                        <div class="tabs-content">
                            <div v-show="tab === 'sources'" class="tab-content">
                                <p class="text-gray-400 text-sm mb-7">
                                    Posters reach the display two ways, and they can be used
                                    together. Add titles yourself and DMP looks up the artwork and
                                    details, or point DMP at a media server and let it sync your
                                    library.
                                </p>

                                <h3 class="text-white font-bold text-lg mb-1">
                                    Adding posters yourself
                                </h3>
                                <p class="text-gray-400 text-sm mb-5">
                                    Used when you add a poster on the Posters screen, either by
                                    searching for a title or by entering an IMDB ID. No media server
                                    is needed for this.
                                </p>

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
                                    <div id="tmdb-v3Help" class="text-gray-400 text-sm">
                                        Required to search for titles and to fill in artwork,
                                        ratings, runtime and trailers. DMP identifies titles by
                                        their IMDB ID, but IMDB has no public API - TMDB is what
                                        answers, and it accepts IMDB IDs.
                                        <a
                                            class="underline hover:text-white"
                                            href="https://www.themoviedb.org/settings/api"
                                            target="_blank"
                                            rel="noopener"
                                            >Get a free key</a
                                        >.
                                    </div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <h3 class="text-white font-bold text-lg mb-1">
                                    Syncing from a media library
                                </h3>
                                <p class="text-gray-400 text-sm mb-5">
                                    Optional, and only needed if you want DMP to follow a media
                                    server. Enabling one does two jobs: it syncs that library into
                                    your poster list, and it can switch the display to whatever is
                                    playing right now. Leave these off if you add posters yourself.
                                </p>

                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="validate-movie-titles"
                                            aria-describedby="validateTitlesHelp"
                                            v-model="settings.validate_movie_titles"
                                        />
                                        <span class="ml-2">Validate movie titles when syncing</span>
                                    </label>
                                    <div id="validateTitlesHelp" class="text-gray-400 text-sm">
                                        Titles have to match exactly before DMP treats two entries
                                        as the same film. Worth having on when more than one of the
                                        services below is syncing, so the same film does not arrive
                                        twice.
                                    </div>
                                </div>

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
                                        Syncs the Plex libraries you pick below into your poster
                                        list, and can switch the display to whatever Plex is
                                        playing. Choose which of the two you want with the
                                        checkboxes further down.
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
                                        for="plex-show-movie-now-playing"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="plex-show-movie-now-playings"
                                            aria-describedby="plex-show-movie-now-playingsHelp"
                                            v-model="settings.plex_show_movie_now_playing"
                                        />
                                        <span class="ml-2">Allow Movie Now Playing</span>
                                    </label>
                                    <div
                                        id="plex-show-movie-now-playingHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="plex-show-tv-now-playing"
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                    >
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="plex-show-movie-now-playings"
                                            aria-describedby="plex-show-movie-now-playingsHelp"
                                            v-model="settings.plex_show_tv_now_playing"
                                        />
                                        <span class="ml-2">Allow TV Now Playing</span>
                                    </label>
                                    <div
                                        id="plex-show-tv-now-playingHelp"
                                        class="text-gray-400 text-sm"
                                    ></div>
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
                                            class="text-gray-300 block mb-2 font-bold flex items-center"
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
                                            class="text-black text-sm bg-white border-2 border-gray-500 px-3 py-2 ml-3 rounded-none hover:bg-gray-700 hover:text-white"
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
                                            class="text-gray-300 block mb-2 font-bold flex items-center"
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
                                            class="text-black text-sm bg-white border-2 border-gray-500 px-3 py-2 ml-3 rounded-none hover:bg-gray-700 hover:text-white"
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
                                        Syncs your Jellyfin movie library into your poster list, and
                                        switches the display to whatever Jellyfin is playing.
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
                                        Syncs your Kodi movie library into your poster list, and
                                        switches the display to whatever Kodi is playing.
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

                            <div v-show="tab === 'power'" class="tab-content">
                                <h3 class="text-white font-bold text-lg mb-1">Screen power</h3>
                                <p class="text-gray-400 text-sm mb-5">
                                    DMP can switch the television off overnight and back on in the
                                    morning over HDMI CEC, so the display is not lit in an empty
                                    room. The television has to have CEC enabled — manufacturers
                                    each have their own name for it.
                                </p>

                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="cec-controls"
                                            aria-describedby="cecControlsHelp"
                                            v-model="settings.use_cec_power"
                                        />
                                        <span class="ml-2"
                                            >Turn the display on and off on a schedule</span
                                        >
                                    </label>
                                    <div id="cecControlsHelp" class="text-gray-400 text-sm">
                                        Off by default. With this off, the screen stays on whenever
                                        the Pi is running.
                                    </div>

                                    <div v-if="settings.use_cec_power" class="mt-3">
                                        <label
                                            for="start-power-time"
                                            class="text-gray-300 block mb-2 font-bold"
                                            >On at</label
                                        >
                                        <input
                                            type="text"
                                            class="text-black"
                                            id="start-power-time"
                                            aria-describedby="startPowerTimeHelp"
                                            v-model="settings.start_power_time"
                                            placeholder="08:00:00"
                                        />
                                        <div
                                            id="startPowerTimeHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            Twenty-four hour clock, as HH:MM:SS.
                                        </div>

                                        <label
                                            for="end-power-time"
                                            class="text-gray-300 block mt-3 mb-2 font-bold"
                                            >Off at</label
                                        >
                                        <input
                                            type="text"
                                            class="text-black"
                                            id="end-power-time"
                                            aria-describedby="endPowerTimeHelp"
                                            v-model="settings.end_power_time"
                                            placeholder="23:00:00"
                                        />
                                        <div
                                            id="endPowerTimeHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            Twenty-four hour clock, as HH:MM:SS.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-show="tab === 'account'" class="tab-content">
                                <p class="text-gray-400 text-sm mb-7">
                                    Change the username and password you sign in with.
                                </p>

                                <div class="mb-7 pb-6 border-b border-gray-700">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            id="require-login"
                                            aria-describedby="requireLoginHelp"
                                            v-model="settings.require_login"
                                        />
                                        <span class="ml-2 font-bold">
                                            Ask for a login to reach these screens
                                        </span>
                                    </label>

                                    <div id="requireLoginHelp" class="text-gray-400 text-sm mt-2">
                                        On by default. The display itself never asks for a login
                                        either way — this covers the poster, settings and voting
                                        screens.
                                    </div>

                                    <div
                                        v-if="!settings.require_login"
                                        class="mt-3 p-3 rounded-sm text-sm"
                                        style="background-color: #4a1d1d; color: #fecaca"
                                    >
                                        <strong
                                            >Anyone who can reach this device can change anything on
                                            it</strong
                                        >
                                        — settings, posters, your media server credentials, and the
                                        update button that runs a script on the Pi. Only leave this
                                        off on a network you trust, and never on one reachable from
                                        the internet.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="account-username"
                                        class="text-gray-300 block mb-2 font-bold"
                                    >
                                        Username
                                    </label>
                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="account-username"
                                        v-model="account.username"
                                        autocomplete="username"
                                        autocapitalize="none"
                                        spellcheck="false"
                                    />
                                    <div class="text-gray-400 text-sm">
                                        Letters, numbers, dashes and underscores. At least three
                                        characters.
                                    </div>
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <h3 class="text-white font-bold text-lg mb-1">Change password</h3>
                                <p class="text-gray-400 text-sm mb-5">
                                    Leave these blank to keep your current password.
                                </p>

                                <div class="mb-5">
                                    <label
                                        for="account-password"
                                        class="text-gray-300 block mb-2 font-bold"
                                    >
                                        New password
                                    </label>
                                    <input
                                        type="password"
                                        class="text-black w-full"
                                        id="account-password"
                                        v-model="account.password"
                                        autocomplete="new-password"
                                    />
                                    <div class="text-gray-400 text-sm">
                                        At least eight characters.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="account-password-confirm"
                                        class="text-gray-300 block mb-2 font-bold"
                                    >
                                        Confirm new password
                                    </label>
                                    <input
                                        type="password"
                                        class="text-black w-full"
                                        id="account-password-confirm"
                                        v-model="account.password_confirmation"
                                        autocomplete="new-password"
                                    />
                                </div>

                                <hr class="mt-3 mb-7 border-gray-700" />

                                <div class="mb-5">
                                    <label
                                        for="account-current"
                                        class="text-gray-300 block mb-2 font-bold"
                                    >
                                        Current password
                                    </label>
                                    <input
                                        type="password"
                                        class="text-black w-full"
                                        id="account-current"
                                        v-model="account.current_password"
                                        autocomplete="current-password"
                                    />
                                    <div class="text-gray-400 text-sm">
                                        Required to save any change here, so a browser left open is
                                        not enough to take the account over.
                                    </div>
                                </div>

                                <div
                                    v-if="accountMessage"
                                    class="px-4 py-3 rounded mb-4"
                                    :class="
                                        accountFailed
                                            ? 'bg-red-900 text-white'
                                            : 'bg-green-900 text-white'
                                    "
                                    role="alert"
                                >
                                    <p>{{ accountMessage }}</p>
                                    <div v-for="(err, i) in accountErrors" :key="'acct-' + i">
                                        {{ err }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="btn text-white px-4 py-2 rounded-sm"
                                    style="background-color: #2563eb"
                                    :disabled="savingAccount"
                                    @click.prevent="saveAccount"
                                >
                                    {{ savingAccount ? 'Updating…' : 'Update account' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import MainNav from '@/partials/MainNav.vue';
import SettingsBar from '@/components/settings-bar.vue';
import UnsavedChangesModal from '@/components/unsaved-changes-modal.vue';
import settingsForm from '@/mixins/settings-form';
import { useAuthStore } from '@/store/auth';

/**
 * How the box is wired up: where posters come from, when the screen is on, and
 * who is allowed in.
 *
 * Everything about what the screen draws moved to the Display page. What is
 * left here is the three things you set up once and rarely touch again, which
 * is why they were the worst possible neighbours for the display options that
 * are fiddled with constantly.
 */
export default {
    name: 'Settings',
    mixins: [settingsForm],
    components: { MainNav, SettingsBar, UnsavedChangesModal },
    data() {
        return {
            tabs: [
                { id: 'sources', label: 'Poster Sources' },
                { id: 'power', label: 'Screen power' },
                { id: 'account', label: 'Account' },
            ],
            plexSections: [],
            plexTvSection: '',
            plexMovieSection: '',
            account: {
                username: '',
                password: '',
                password_confirmation: '',
                current_password: '',
            },
            savingAccount: false,
            accountMessage: '',
            accountFailed: false,
            accountErrors: [],
        };
    },
    computed: {
        plexTvSections() {
            return this.plexSections.filter((item) => item.type === 'show');
        },
        plexMovieSections() {
            return this.plexSections.filter((item) => item.type === 'movie');
        },
    },
    mounted() {
        const auth = useAuthStore();
        Promise.resolve(auth.loadStatus()).then(() => {
            this.account.username = auth.user ? auth.user.username : '';
        });
    },
    methods: {
        /** Called once the settings arrive, when we know whether Plex is on. */
        settingsLoaded() {
            if (this.settings.plex_service) {
                this.getServiceSections('plex');
            }
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
        getMovieLibraryName(service, key) {
            if (service === 'plex') {
                const obj = this.plexMovieSections.find((item) => item.key === key);

                return obj ? obj.title : '';
            }
        },
        getTvLibraryName(service, key) {
            if (service === 'plex') {
                const obj = this.plexTvSections.find((item) => item.key === key);

                return obj ? obj.title : '';
            }
        },
        addMovieSyncLibrary(service) {
            if (service === 'plex' && this.plexMovieSection) {
                if (!this.settings.plex_movie_sections) {
                    this.settings.plex_movie_sections = [];
                }
                if (!this.settings.plex_movie_sections.includes(this.plexMovieSection)) {
                    this.settings.plex_movie_sections.push(this.plexMovieSection);
                }
            }
        },
        addTvSyncLibrary(service) {
            if (service === 'plex' && this.plexTvSection) {
                if (!this.settings.plex_tv_sections) {
                    this.settings.plex_tv_sections = [];
                }
                if (!this.settings.plex_tv_sections.includes(this.plexTvSection)) {
                    this.settings.plex_tv_sections.push(this.plexTvSection);
                }
            }
        },
        removeMovieSyncLibrary(service, item) {
            if (service === 'plex') {
                this.settings.plex_movie_sections.splice(
                    this.settings.plex_movie_sections.indexOf(item),
                    1,
                );
            }
        },
        removeTvSyncLibrary(service, item) {
            if (service === 'plex') {
                this.settings.plex_tv_sections.splice(
                    this.settings.plex_tv_sections.indexOf(item),
                    1,
                );
            }
        },
        /**
         * The account form is deliberately separate from the settings save
         * bar: it posts elsewhere, needs the current password, and should not
         * be swept along by "Save settings".
         */
        saveAccount() {
            if (this.savingAccount) {
                return;
            }

            this.savingAccount = true;
            this.accountMessage = '';
            this.accountFailed = false;
            this.accountErrors = [];

            axios
                .put('/api/auth/account', this.account)
                .then(({ data }) => {
                    this.accountMessage = data.password_changed
                        ? 'Account updated. Your new password applies the next time you sign in.'
                        : 'Account updated.';
                    this.account.password = '';
                    this.account.password_confirmation = '';
                    this.account.current_password = '';
                    useAuthStore().loadStatus(true);
                })
                .catch((error) => {
                    this.accountFailed = true;
                    const body = error.response && error.response.data;
                    this.accountMessage =
                        (body && body.message) || 'The account could not be updated.';

                    const errors = (body && body.errors) || {};
                    Object.keys(errors).forEach((field) => {
                        if (errors[field] instanceof Array) {
                            errors[field].forEach((err) => this.accountErrors.push(err));
                        }
                    });

                    // Laravel repeats the first error in "message".
                    if (this.accountErrors.length) {
                        this.accountMessage = 'That change could not be saved:';
                    }
                })
                .finally(() => {
                    this.savingAccount = false;
                });
        },
    },
    beforeRouteLeave(to, from, next) {
        this.confirmLeave(to, from, next);
    },
};
</script>

<style scoped lang="scss">
input[type='text'],
input[type='number'] {
    height: 42px;
    border-radius: 2px;
}

.tabs-content {
    margin-bottom: 24px;
    position: relative;

    .tab-content {
        padding: 24px;
        border-top: 1px solid #555;
    }
}
</style>
