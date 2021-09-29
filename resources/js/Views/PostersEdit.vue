<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-2">
                        <ul
                            class="block text-gray-300 p-4 sticky top-0"
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
                            <li>
                                <router-link class="hover:text-gray-500" to="/settings"
                                    >Settings</router-link
                                >
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-10 p-4 relative" style="background-color: #121212">
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
                                        id="movie-poster"
                                        aria-describedby="movie-posterHelp"
                                        @change="selectFile"
                                    />
                                    <div id="movie-posterHelp" class="text-gray-400 text-sm"></div>
                                </div>

                                <div class="mb-5 bg-gray-700 p-3">
                                    <label for="music" class="text-gray-300 block mb-2 font-bold"
                                        >Theme Music</label
                                    >

                                    <input
                                        type="file"
                                        class="text-black w-full"
                                        id="music"
                                        aria-describedby="musicHelp"
                                        @change="selectMusicFile"
                                    />
                                    <div id="musicHelp" class="text-gray-400 text-sm">
                                        MP3 Theme music.
                                        <span v-if="poster.theme_music_path"
                                            >Current File: {{ poster.theme_music_path }}</span
                                        >
                                    </div>
                                </div>

                                <div class="mb-5">
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
                                        >YouTube Movie ID</label
                                    >

                                    <input
                                        type="text"
                                        class="text-black w-full"
                                        id="trailer-path"
                                        v-model="poster.trailer_path"
                                    />
                                </div>

                                <div class="mb-5">
                                    <label class="text-gray-300 block mb-2 font-bold"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_trailer"
                                        />
                                        Show Trailer</label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label class="text-gray-300 block mb-2 font-bold"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_runtime"
                                        />
                                        Show Runtime</label
                                    >
                                </div>
                                <div class="mb-5">
                                    <label class="text-gray-300 block mb-2 font-bold"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.show_in_rotation"
                                        />
                                        Show in Rotation</label
                                    >
                                </div>

                                <div class="mb-5">
                                    <label class="text-gray-300 block mb-2 font-bold"
                                        ><input
                                            type="checkbox"
                                            class="text-black"
                                            v-model="poster.play_theme_music"
                                        />
                                        Play Theme Music</label
                                    >
                                </div>

                                <div class="py-3 text-white">
                                    {{ formMessage }}
                                    <div v-for="error in errors">{{ error }}</div>
                                </div>

                                <button
                                    class="
                                        btn
                                        text-black
                                        bg-gray-300
                                        text-md
                                        px-3
                                        py-1
                                        hover:bg-gray-100
                                    "
                                    @click.prevent="savePoster"
                                    :disabled="saving"
                                >
                                    {{ savePosterBtn }}
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
            poster: {
                id: '',
                imdb_id: '',
                name: '',
                image: null,
                music: null,
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
        };
    },
    components: {},
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
        selectFile() {
            this.poster.image = event.target.files[0];
        },
        selectMusicFile() {
            this.poster.music = event.target.files[0];
        },
        savePoster() {
            this.errors = [];
            this.formMessage = '';
            this.saving = true;
            this.savePosterBtn = 'Saving ...';
            let url = '';
            const error = this.validated();

            if (error) {
                return false;
            }

            const params = new FormData();
            if (this.poster.image) {
                params.append('image', this.poster.image);
            }
            if (this.poster.music) {
                params.append('music', this.poster.music);
            }

            if (this.poster.name) {
                params.append('title', this.poster.name);
            }
            if (this.poster.imdb_id) {
                params.append('imdb_id', this.poster.imdb_id);
            }
            if (this.poster.mpaa_rating) {
                params.append('mpaa_rating', this.poster.mpaa_rating);
            }
            if (this.poster.audience_rating) {
                params.append('audience_rating', this.poster.audience_rating);
            }
            if (this.poster.trailer_path) {
                params.append('trailer_path', this.poster.trailer_path);
            }
            if (this.poster.runtime) {
                params.append('runtime', this.poster.runtime);
            }

            params.append('show_trailer', this.poster.show_trailer);
            params.append('show_runtime', this.poster.show_runtime);
            params.append('show_in_rotation', this.poster.show_in_rotation);
            params.append('play_theme_music', this.poster.play_theme_music);

            if (this.mode === 'edit') {
                params.append('_method', 'put');
                url = '/api/posters/' + this.poster.id;
            } else {
                url = '/api/posters';
            }

            axios
                .post(url, params)
                .then((response) => {
                    this.saving = false;
                    this.savePosterBtn = 'Saved Poster!';

                    this.poster = response.data.poster;
                    this.mode = 'edit';
                    this.poster.music = null;
                    this.poster.image = null;

                    setTimeout(() => {
                        this.savePosterBtn = 'Save Poster';
                    }, 3000);
                })
                .catch((e) => {
                    console.log(e.message);
                    this.saving = false;
                    this.savePosterBtn = 'Save Poster';
                    this.errors = e.response.data.errors;
                    this.formMessage = e.response.data.message;
                });
        },
        validated() {
            this.formMessage = '';
            let error = false;

            if (this.poster.title === '' && this.poster.imdb_id === '') {
                error = true;
                this.formMessage = 'IMDB ID or the title is required.';
            }

            if (this.poster.image === '' && this.poster.imdb_id === '') {
                error = true;
                this.formMessage = 'IMBD ID or poster image is required.';
            }

            if (error) {
                this.saving = false;
                this.savePosterBtn = 'Save Poster';
            }

            return error;
        },
    },
    created() {},
    mounted() {
        const id = parseInt(this.$route.params.id);
        if (id === 0) {
            this.mode = 'new';
        } else {
            this.mode = 'edit';
            this.getPoster(id);
        }
    },
};
</script>

<style lang="scss"></style>
