<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-3">
                        <main-nav />
                    </div>
                    <div class="lg:col-span-9 p-4 relative" style="background-color: #121212">
                        <div class="grid lg:grid-cols-12 gap-4">
                            <div class="lg:col-span-4">
                                <img
                                    v-if="mode === 'edit' && poster.file_name"
                                    :src="'/storage/posters/' + poster.file_name"
                                    alt="Poster"
                                    class="mb-5"
                                    style="max-width: 100%; height: auto"
                                />
                            </div>
                            <div class="lg:col-span-8">
                                <div class="mb-5">
                                    <label
                                        for="movie-title"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >IMDB ID</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="movie-title"
                                        v-model="poster.imdb_id"
                                        required
                                    />
                                    <div id="movie-posterHelp" class="text-gray-400 text-sm">
                                        If used, TMDB API will override movie data for this poster
                                        except theme music and settings.
                                    </div>
                                </div>

                                <hr class="border-grey-500 mb-5" />

                                <div class="mb-5">
                                    <label
                                        for="media-type"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Media Type</label
                                    >

                                    <select
                                        class="text-black w-full"
                                        id="media-type"
                                        v-model="poster.media_type"
                                        required
                                    >
                                        <option value="movie">Movie</option>
                                        <option value="tv">TV</option>
                                    </select>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="movie-title"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Poster Name</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="movie-title"
                                        v-model="poster.name"
                                        required
                                    />
                                </div>

                                <div class="mb-5 bg-gray-700 p-3">
                                    <label
                                        for="movie-poster"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Movie Poster</label
                                    >

                                    <input
                                        type="file"
                                        class="text-black w-full"
                                        aria-describedby="movie-posterHelp"
                                        @change="selectFile($event)"
                                    />
                                    <div id="movie-posterHelp" class="text-gray-400 text-sm"></div>
                                </div>

                                <div class="mb-5 bg-gray-700 p-3">
                                    <label
                                        for="music-input"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Theme Music</label
                                    >

                                    <input
                                        type="file"
                                        class="text-black w-full"
                                        name="music_file"
                                        id="music-unput"
                                        aria-describedby="musicHelp"
                                        @change="selectMusicFile($event)"
                                    />
                                    <div id="musicHelp" class="text-gray-400 text-sm">
                                        MP3 Theme music.
                                        <span v-if="poster.theme_music_path"
                                            >Current File: {{ poster.theme_music_path }}</span
                                        >
                                    </div>
                                </div>

                                <div class="mb-5" v-if="poster.media_type === 'movie'">
                                    <label
                                        for="content-rating"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Content Rating</label
                                    >

                                    <select
                                        class="text-black w-full"
                                        id="mpaa-rating"
                                        v-model="poster.mpaa_rating"
                                    >
                                        <option value="">Not Rated</option>
                                        <option value="G">G</option>
                                        <option value="PG">PG</option>
                                        <option value="PG-13">PG-13</option>
                                        <option value="R">R</option>
                                        <option value="NC-17">NC-17</option>
                                    </select>
                                </div>

                                <div class="mb-5" v-if="poster.media_type === 'tv'">
                                    <label
                                        for="content-rating"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Content Rating</label
                                    >

                                    <select
                                        class="text-black w-full"
                                        id="mpaa-rating"
                                        v-model="poster.mpaa_rating"
                                    >
                                        <option value="">Not Rated</option>
                                        <option value="TV-Y">TV-Y</option>
                                        <option value="TV-Y7">TV-Y7</option>
                                        <option value="TV-Y7 FV">TV-Y7 FV</option>
                                        <option value="TV-G">TV-G</option>
                                        <option value="TV-PG">TV-PG</option>
                                        <option value="TV-14">TV-14</option>
                                        <option value="TV-MA">TV-MA</option>
                                    </select>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="audience-rating"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >Audience Rating</label
                                    >

                                    <input
                                        type="number"
                                        class="text-black w-full"
                                        id="audience-rating"
                                        v-model="poster.audience_rating"
                                        step="0.01"
                                        min="0"
                                        max="10"
                                    />
                                    <div id="audience-ratingHelp" class="text-gray-400 text-sm">
                                        Audience rating. 1-10.
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label for="runtime" class="text-gray-300 block mb-2 font-bold"
                                        >Runtime (in minutes)</label
                                    >

                                    <input
                                        type="number"
                                        class="text-black w-full"
                                        id="runtime"
                                        v-model="poster.runtime"
                                        step="1"
                                        min="0"
                                        max="400"
                                    />
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="trailer-path"
                                        class="text-gray-300 block mb-2 font-bold"
                                        >YouTube ID</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="trailer-path"
                                        v-model="poster.trailer_path"
                                    />
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_trailer"
                                        />
                                        <span class="ml-2">Show Trailer</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_runtime"
                                        />
                                        <span class="ml-2">Show Runtime</span></label
                                    >
                                </div>
                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_in_rotation"
                                        />
                                        <span class="ml-2">Show in Rotation</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.play_theme_music"
                                        />
                                        <span class="ml-2">Play Theme Music</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_dolby_atmos"
                                        />
                                        <span class="ml-2">Show Dolby Atmos Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_dolby_51"
                                        />
                                        <span class="ml-2">Show Dolby 5.1 Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_dolby_vision"
                                        />
                                        <span class="ml-2">Show Dolby Vision Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_dtsx"
                                        />
                                        <span class="ml-2">Show DTS:X Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_auro_3d"
                                        />
                                        <span class="ml-2">Show Auro 3D Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label
                                        class="text-gray-300 block mb-2 font-bold flex items-center"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_imax"
                                        />
                                        <span class="ml-2">Show IMAX Enhanced Logo</span></label
                                    >
                                </div>

                                <div class="mb-5">
                                    <h5
                                        v-if="formMessage.length"
                                        class="text-white text-lg font-bold mb-2"
                                    >
                                        {{ formMessage }}
                                    </h5>
                                    <div
                                        v-if="errors.length > 0"
                                        class="bg-red-700 text-white px-3 py-2"
                                    >
                                        <div
                                            v-for="(error, eIndex) in errors"
                                            :key="'err-' + eIndex"
                                        >
                                            {{ error }}
                                        </div>
                                    </div>
                                </div>

                                <button
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
                                    @click.prevent="savePoster"
                                    :disabled="saving"
                                >
                                    {{ savePosterBtn }}
                                </button>

                                <a
                                    href="#"
                                    v-if="mode === 'edit'"
                                    @click.prevent="clearPoster()"
                                    class="inline ml-5 text-white"
                                    >Reset to create new poster</a
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MainNav from '../partials/MainNav.vue';

export default {
    data: function () {
        return {
            loading: false,
            saving: false,
            errors: [],
            recaching: false,
            debounce: false,
            showPosterModal: false,
            formMessage: '',
            mode: '',
            music: null,
            poster: {
                id: '',
                imdb_id: '',
                media_type: 'movie',
                name: '',
                image: null,
                mpaa_rating: '',
                audience_rating: '',
                trailer_path: '',
                show_trailer: false,
                show_runtime: true,
                show_in_rotation: true,
                runtime: '',
                play_theme_music: false,
            },
            savePosterBtn: 'Save Poster',
            socket: '',
        };
    },
    components: { MainNav },
    watch: {},
    methods: {
        getPoster(id) {
            axios
                .get('/api/posters/' + id)
                .then((response) => {
                    this.poster = response.data.poster;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        selectFile(event) {
            this.poster.image = event.target.files[0];
        },
        selectMusicFile(event) {
            this.music = event.target.files[0];
        },
        savePoster() {
            this.errors = [];
            this.formMessage = '';
            this.saving = true;
            this.savePosterBtn = 'Saving ...';
            let url = '';
            let error = this.validated();

            if (error) {
                return false;
            }

            let params = {};
            if (this.poster.image) {
                params.image = this.poster.image;
            }
            if (this.poster.image) {
                params.music = this.music;
            }
            params.name = this.poster.name;
            params.imdb_id = this.poster.imdb_id;
            params.media_type = this.poster.media_type;
            params.mpaa_rating = this.poster.mpaa_rating;
            params.audience_rating = this.poster.audience_rating;
            params.trailer_path = this.poster.trailer_path;
            params.runtime = this.poster.runtime;
            params.show_trailer = this.poster.show_trailer;
            params.show_runtime = this.poster.show_runtime;
            params.show_in_rotation = this.poster.show_in_rotation;
            params.play_theme_music = this.poster.play_theme_music;
            params.show_dolby_atmos = this.poster.show_dolby_atmos;
            params.show_dolby_51 = this.poster.show_dolby_51;
            params.show_dolby_vision = this.poster.show_dolby_vision;
            params.show_dtsx = this.poster.show_dtsx;
            params.show_auro_3d = this.poster.show_auro_3d;
            params.show_imax = this.poster.show_imax;

            if (this.mode === 'edit') {
                params._method = 'put';
                url = '/api/posters/' + this.poster.id;
            } else {
                url = '/api/posters';
            }
            const headers = { 'Content-Type': 'multipart/form-data' };
            axios
                .post(url, params, { headers })
                .then((response) => {
                    this.saving = false;
                    this.savePosterBtn = 'Saved Poster!';

                    this.poster = response.data.poster;
                    this.mode = 'edit';
                    this.music = null;
                    this.poster.image = null;

                    setTimeout(() => {
                        this.setSavePosterBtn();
                    }, 2000);
                })
                .catch((e) => {
                    this.saving = false;
                    this.setSavePosterBtn();
                    let errors = e.response.data.errors;
                    this.formMessage = e.response.data.message;

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
        clearPoster() {
            for (const prop of Object.getOwnPropertyNames(this.poster)) {
                this.poster[prop] = '';
            }
            this.poster.id = 0;
            this.poster.show_runtime = 1;
            this.poster.show_in_rotation = 1;
            this.poster.image = null;
            this.music = null;
            this.mode = 'new';
            this.setSavePosterBtn();
        },
        validated() {
            this.formMessage = '';
            let error = false;

            if (this.poster.name === '' && this.poster.imdb_id === '') {
                error = true;
                this.formMessage = 'IMDB ID or the title is required.';
            }

            if (this.poster.image === '' && this.poster.imdb_id === '') {
                error = true;
                this.formMessage = 'IMBD ID or poster image is required.';
            }

            if (error) {
                this.saving = false;
                this.setSavePosterBtn();
            }

            return error;
        },
        setSavePosterBtn() {
            this.savePosterBtn = this.mode === 'edit' ? 'Update Poster' : 'Create Poster';
        },
    },
    created() {},
    mounted() {
        const id = parseInt(this.$route.params.id);
        this.poster.id = id;
        if (id === 0) {
            this.mode = 'new';
        } else {
            this.mode = 'edit';
            this.getPoster(id);
        }
        this.setSavePosterBtn();
    },
};
</script>

<style scoped lang="scss">
input[type='text'],
input[type='number'] {
    height: 42px;
    border-radius: 2px;
}
</style>
