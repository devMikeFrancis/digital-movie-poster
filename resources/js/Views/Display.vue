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
                            <div v-show="tab === 'slideshow'" class="tab-content">
                                <h3 class="text-xl font-bold text-white mb-5">How posters cycle</h3>
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
                                        Transition
                                    </label>
                                    <select
                                        class="text-black"
                                        id="type"
                                        aria-describedby="typeHelp"
                                        v-model="settings.transition_type"
                                    >
                                        <option value="fade">Fade</option>
                                        <option value="crossfade">Cross-fade</option>
                                        <option value="vertical">Vertical</option>
                                        <option value="cut">Cut</option>
                                    </select>

                                    <div id="typeHelp" class="text-gray-400 text-sm">
                                        How one poster gives way to the next. Fade takes both
                                        through the background, so the screen dips darker in
                                        between; cross-fade brings the new one in over the old, so
                                        it does not. Vertical slides upward, and cut swaps them
                                        outright with no animation.
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="display-speed"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Time on screen</label
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
                                <h3 class="text-xl font-bold text-white mb-5">
                                    How they are framed
                                </h3>
                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            id="fill-screen"
                                            aria-describedby="fillScreenHelp"
                                            v-model="settings.poster_fill_screen"
                                        />
                                        <span class="ml-2">Fill the screen with the poster</span>
                                    </label>
                                    <div id="fillScreenHelp" class="text-gray-400 text-sm">
                                        The poster takes the whole display instead of sitting in a
                                        box between the header and footer, which float over it
                                        instead. It is scaled to fit rather than cropped, so it
                                        keeps its shape and nothing is cut off.
                                    </div>

                                    <div v-if="settings.poster_fill_screen" class="mt-3">
                                        <label
                                            for="fill-scrim"
                                            class="text-gray-300 block mb-2 font-bold"
                                        >
                                            Shading behind the header and footer
                                        </label>
                                        <select
                                            class="text-black"
                                            id="fill-scrim"
                                            aria-describedby="fillScrimHelp"
                                            v-model="settings.poster_fill_scrim"
                                        >
                                            <option value="none">None</option>
                                            <option value="subtle">Subtle</option>
                                            <option value="standard">Standard</option>
                                            <option value="strong">Strong</option>
                                        </select>
                                        <div id="fillScrimHelp" class="text-gray-400 text-sm mt-1">
                                            Darkens the top and bottom of the screen so the header
                                            and footer text stays readable over the artwork. A dark
                                            poster needs none of it; a bright one needs quite a lot.
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
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
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="poster-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Behind the poster</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="poster-bg-color"
                                        aria-describedby="poster-bg-color-textHelp"
                                        v-model="settings.poster_bg_color"
                                    />
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />
                                <h3 class="text-xl font-bold text-white mb-5">
                                    What is allowed on screen
                                </h3>
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
                            </div>

                            <div v-show="tab === 'header'" class="tab-content">
                                <h3 class="text-xl font-bold text-white mb-5">
                                    The Coming Soon / Now Playing text
                                </h3>
                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            id="show-header-text"
                                            aria-describedby="showHeaderTextHelp"
                                            v-model="settings.show_header_text"
                                        />
                                        <span class="ml-2">
                                            Show the Coming Soon / Now Playing text
                                        </span>
                                    </label>
                                    <div id="showHeaderTextHelp" class="text-gray-400 text-sm">
                                        Turn this off for a display that shows only artwork. The
                                        runtime and the rest of the header are unaffected.
                                    </div>

                                    <div v-if="settings.show_header_text" class="mt-3">
                                        <label
                                            for="header-style"
                                            class="text-gray-300 block mb-2 font-bold"
                                        >
                                            Header plate
                                        </label>
                                        <select
                                            class="text-black"
                                            id="header-style"
                                            aria-describedby="headerStyleHelp"
                                            v-model="settings.header_style"
                                        >
                                            <option value="plain">Plain</option>
                                            <option value="rules">Rules either side</option>
                                            <option value="marquee">Marquee bulbs</option>
                                            <option value="plaque">Plaque</option>
                                            <option value="neon">Neon glow</option>
                                        </select>
                                        <div
                                            id="headerStyleHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            Plaque is the box the header used to have, and keeps its
                                            own border colour, set below.
                                        </div>

                                        <label
                                            for="header-position"
                                            class="text-gray-300 block mt-3 mb-2 font-bold"
                                        >
                                            Where it sits
                                        </label>
                                        <select
                                            class="text-black"
                                            id="header-position"
                                            v-model="settings.header_position"
                                        >
                                            <option value="top">Above the poster</option>
                                            <option value="bottom">Below the poster</option>
                                        </select>

                                        <label class="text-gray-300 flex items-center mt-3">
                                            <input
                                                type="checkbox"
                                                id="header-full-width"
                                                v-model="settings.header_full_width"
                                            />
                                            <span class="ml-2">Span the width of the screen</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="coming-soon-text"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Coming Soon wording</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="coming-soon-text"
                                        aria-describedby="coming-soon-textHelp"
                                        v-model="settings.coming_soon_text"
                                    />
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="now-playing-text"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Now Playing wording</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="now-playing-text"
                                        aria-describedby="now-playing-textHelp"
                                        v-model="settings.now_playing_text"
                                    />
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="header-font"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Font</label
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
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="header-font-size"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Font size</label
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
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="header-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Header background</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-bg-color"
                                        aria-describedby="header-bg-color-textHelp"
                                        v-model="settings.header_bg_color"
                                    />
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="header-text-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Header text colour</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-text-color"
                                        aria-describedby="header-text-color-textHelp"
                                        v-model="settings.header_text_color"
                                    />
                                </div>
                                <div v-if="settings.header_style === 'plaque'" class="mb-5">
                                    <label
                                        for="header-border-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Plaque border colour</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="header-border-color"
                                        aria-describedby="header-border-color-textHelp"
                                        v-model="settings.header_border_color"
                                    />
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />
                                <h3 class="text-xl font-bold text-white mb-5">The theater name</h3>
                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            id="show-theater-name"
                                            aria-describedby="showTheaterNameHelp"
                                            v-model="settings.show_theater_name"
                                        />
                                        <span class="ml-2">Show the theater name</span>
                                    </label>
                                    <div id="showTheaterNameHelp" class="text-gray-400 text-sm">
                                        The name of the room this display is in.
                                    </div>

                                    <div v-if="settings.show_theater_name" class="mt-3">
                                        <label
                                            for="theater-name"
                                            class="text-gray-300 block mb-2 font-bold"
                                            >Name</label
                                        >
                                        <input
                                            type="text"
                                            class="text-black w-full mb-3"
                                            id="theater-name"
                                            maxlength="120"
                                            placeholder="The Roxy"
                                            v-model="settings.theater_name"
                                        />
                                        <label
                                            for="theater-name-position"
                                            class="text-gray-300 block mb-2 font-bold"
                                            >Where it sits</label
                                        >
                                        <select
                                            class="text-black"
                                            id="theater-name-position"
                                            v-model="settings.theater_name_position"
                                        >
                                            <option value="top">Above the poster</option>
                                            <option value="bottom">Below the poster</option>
                                        </select>

                                        <label
                                            for="theater-name-style"
                                            class="text-gray-300 block mt-3 mb-2 font-bold"
                                        >
                                            Name plate
                                        </label>
                                        <select
                                            class="text-black"
                                            id="theater-name-style"
                                            aria-describedby="theaterNameStyleHelp"
                                            v-model="settings.theater_name_style"
                                        >
                                            <option value="plain">Plain</option>
                                            <option value="rules">Rules either side</option>
                                            <option value="marquee">Marquee bulbs</option>
                                            <option value="plaque">Plaque</option>
                                            <option value="neon">Neon glow</option>
                                        </select>
                                        <div
                                            id="theaterNameStyleHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            All of them are drawn in the header's text colour and
                                            font, so the name matches the rest of the screen.
                                        </div>

                                        <label class="text-gray-300 flex items-center mt-3">
                                            <input
                                                type="checkbox"
                                                id="theater-name-full-width"
                                                v-model="settings.theater_name_full_width"
                                            />
                                            <span class="ml-2">Span the width of the screen</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-show="tab === 'details'" class="tab-content">
                                <h3 class="text-xl font-bold text-white mb-5">
                                    Details shown with the poster
                                </h3>
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
                                        Shown in the footer, beside the rating.
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
                                        Shows the audience rating.
                                    </div>
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />
                                <h3 class="text-xl font-bold text-white mb-5">Speaker config</h3>
                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="show-speaker-config"
                                            aria-describedby="showSpeakerConfigHelp"
                                            v-model="settings.show_speaker_config"
                                        />
                                        <span class="ml-2">Show speaker config</span>
                                    </label>
                                    <div id="showSpeakerConfigHelp" class="text-gray-400 text-sm">
                                        A small badge naming the room's speaker layout.
                                    </div>

                                    <div v-if="settings.show_speaker_config" class="mt-3">
                                        <label
                                            for="speaker-config"
                                            class="text-gray-300 block mb-2 font-bold"
                                            >Layout</label
                                        >
                                        <input
                                            type="text"
                                            class="text-black"
                                            id="speaker-config"
                                            aria-describedby="speakerConfigHelp"
                                            v-model="settings.speaker_config"
                                            @input="formatSpeakerConfig"
                                            maxlength="12"
                                        />
                                        <div
                                            id="speakerConfigHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            Such as 5.1, 7.1.2 or 9.4.6. It sits in the footer with
                                            the rating and the logos.
                                        </div>
                                    </div>
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />
                                <h3 class="text-xl font-bold text-white mb-5">Processing logos</h3>
                                <div class="mb-5">
                                    <label class="text-gray-300 inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            class="text-black"
                                            id="show-processing-logos"
                                            aria-describedby="showProcessingLogosHelp"
                                            v-model="settings.show_processing_logos"
                                        />
                                        <span class="ml-2">Show processing logos</span>
                                    </label>
                                    <div id="showProcessingLogosHelp" class="text-gray-400 text-sm">
                                        The Dolby Atmos, Dolby Vision, DTS:X, IMAX and Auro-3D
                                        badges along the bottom of the screen.
                                    </div>

                                    <div v-if="settings.show_processing_logos" class="mt-3">
                                        <label
                                            for="prologo-source"
                                            class="text-gray-300 block mb-2 font-bold"
                                        >
                                            Which logos to show
                                        </label>
                                        <select
                                            class="text-black"
                                            id="prologo-source"
                                            aria-describedby="prologoSourceHelp"
                                            v-model="prologoSource"
                                        >
                                            <option value="poster">
                                                Only the ones each title supports
                                            </option>
                                            <option value="poster-then-global">
                                                Each title's own, or the ones below if it has none
                                            </option>
                                            <option value="global">
                                                The ones below, on every title
                                            </option>
                                        </select>
                                        <div
                                            id="prologoSourceHelp"
                                            class="text-gray-400 text-sm mt-1"
                                        >
                                            A title's own formats are set when you edit it. The last
                                            option ignores them and shows the same logos everywhere,
                                            which is why a film with no Atmos soundtrack can end up
                                            displaying the Atmos logo.
                                        </div>

                                        <div v-if="prologoSource !== 'poster'" class="mt-4">
                                            <p class="text-gray-300 mb-2 font-bold">
                                                Formats to show
                                            </p>
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_dolby_51"
                                                />
                                                <span class="ml-2">Dolby Digital 5.1</span></label
                                            >
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_dolby_atmos_vertical"
                                                />
                                                <span class="ml-2">Dolby Atmos</span></label
                                            >
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_dolby_vision_vertical"
                                                />
                                                <span class="ml-2">Dolby Vision</span></label
                                            >
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_dts"
                                                />
                                                <span class="ml-2">DTS:X</span></label
                                            >
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_imax"
                                                />
                                                <span class="ml-2">IMAX Enhanced</span></label
                                            >
                                            <label
                                                class="text-gray-300 block mb-2 flex items-center"
                                                ><input
                                                    class="text-black"
                                                    type="checkbox"
                                                    v-model="settings.show_auro_3d"
                                                />
                                                <span class="ml-2">Auro-3D</span></label
                                            >
                                        </div>
                                    </div>
                                </div>
                                <hr class="mt-3 mb-7 border-gray-700" />
                                <h3 class="text-xl font-bold text-white mb-5">Footer colours</h3>
                                <div class="mb-5">
                                    <label
                                        for="footer-bg-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Footer background</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="footer-bg-color"
                                        aria-describedby="footer-bg-color-textHelp"
                                        v-model="settings.footer_bg_color"
                                    />
                                </div>
                                <div class="mb-5">
                                    <label
                                        for="footer-text-color"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Footer text colour</label
                                    >

                                    <input
                                        type="color"
                                        class="w-full"
                                        id="footer-text-color"
                                        aria-describedby="footer-text-color-textHelp"
                                        v-model="settings.footer_text_color"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MainNav from '@/partials/MainNav.vue';
import SettingsBar from '@/components/settings-bar.vue';
import UnsavedChangesModal from '@/components/unsaved-changes-modal.vue';
import settingsForm from '@/mixins/settings-form';

/**
 * Everything that decides what the poster screen looks like.
 *
 * Split out of Settings, where all of this lived on one tab of thirty-eight
 * controls under a heading - "Global Options" - that covered most of them and
 * described none of them. The seam is the same one the documentation already
 * uses: this screen is docs/display.md, and what is left on Settings is how the
 * box is wired up rather than what it draws.
 *
 * Appearance sits with the thing it applies to rather than in a Theme tab of
 * its own. The header's font and colours were three clicks away from the header
 * text they styled, which is why the docs had to tell you where to look.
 */
export default {
    name: 'DisplaySettings',
    mixins: [settingsForm],
    components: { MainNav, SettingsBar, UnsavedChangesModal },
    data() {
        return {
            tabs: [
                { id: 'slideshow', label: 'Slideshow' },
                { id: 'header', label: 'Header & name' },
                { id: 'details', label: 'Poster details' },
            ],
        };
    },
    computed: {
        /**
         * Two stored flags, one decision.
         *
         * They were two checkboxes, and the pair could be set to combinations
         * that contradict each other. The stored shape is left alone so that no
         * display changes on updating; only the way it is asked about changed.
         */
        prologoSource: {
            get() {
                if (this.settings.use_global_prologos) {
                    return 'global';
                }

                return this.settings.use_global_prologos_if_no_poster_prologos
                    ? 'poster-then-global'
                    : 'poster';
            },
            set(value) {
                this.settings.use_global_prologos = value === 'global';
                this.settings.use_global_prologos_if_no_poster_prologos =
                    value === 'poster-then-global';
            },
        },
    },
    methods: {
        formatSpeakerConfig(input) {
            this.settings.speaker_config = input.target.value.replace(/[^1-9.]/g, '');
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
