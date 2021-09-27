<template>
    <div>
        <div class="admin">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="pt-4 pb-4">
                    <router-link class="text-white" to="/"
                        >&larr; Back to movie posters</router-link
                    >
                </div>
            </div>

            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-2 pl-5">
                        <ul
                            class="block text-gray-300 p-4 sticky top-0"
                            style="background-color: #121212"
                        >
                            <li class="mb-3">
                                <router-link class="hover:text-gray-500 active" to="/posters"
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
                    <div class="col-span-10 p-4 relative" style="background-color: #121212">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-6">
                                <button
                                    type="submit"
                                    class="
                                        btn
                                        text-white
                                        bg-blue-600
                                        text-md
                                        px-3
                                        py-1
                                        hover:bg-blue-400
                                    "
                                    @click.prevent="
                                        showPosterModal = true;
                                        edit = false;
                                    "
                                >
                                    <span>&plus; Add Poster</span>
                                </button>
                            </div>
                            <div class="col-span-6 flex justify-end mb-4">
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
                                    @click.prevent="cachePosters"
                                    :disabled="recaching"
                                >
                                    <span v-show="!recaching">Re-cache Posters</span>
                                    <span v-show="recaching">Caching Posters ...</span>
                                </button>
                            </div>
                        </div>
                        <div class="cache-overlay" v-if="recaching" v-cloak>
                            <span>Caching Posters ...</span>
                        </div>
                        <draggable
                            v-model="posters"
                            group="posters"
                            @start="onDragStart"
                            @end="onDragEnd"
                        >
                            <div
                                v-for="poster in posters"
                                :key="poster.id"
                                class="mb-5"
                                style="background-color: #222"
                            >
                                <div class="grid grid-cols-12 gap-4">
                                    <div class="col-span-1 w-12">
                                        <div
                                            class="poster-image-block handle"
                                            :style="
                                                'background-image: url(storage/posters/' +
                                                poster.file_name +
                                                ')'
                                            "
                                        ></div>
                                    </div>
                                    <div class="col-span-6 flex items-center">
                                        <span
                                            class="text-gray-300 font-bold cursor-pointer"
                                            @click="editPoster(poster)"
                                            >{{ poster.name }}</span
                                        >
                                    </div>

                                    <div class="col-span-3 flex items-center">
                                        <label class="text-white"
                                            ><input
                                                type="checkbox"
                                                v-model="poster.show_in_rotation"
                                                @change="updateShowInRotation(poster)"
                                            />
                                            Show in rotation</label
                                        >
                                    </div>

                                    <div class="col-span-2 flex items-center justify-end pr-4">
                                        <a
                                            href="#"
                                            @click.prevent="deletePoster(poster)"
                                            class="text-gray-300 font-bold hover:text-gray-50"
                                            >Delete <span v-if="!poster.can_delete">Cached</span></a
                                        >
                                    </div>
                                </div>
                            </div>
                        </draggable>
                    </div>
                </div>
            </div>

            <div class="poster-form-model" v-bind:class="{ show: showPosterModal }">
                <div class="inner">
                    <a
                        href="#"
                        class="close"
                        @click.prevent="
                            showPosterModal = false;
                            edit = false;
                        "
                        >&times;</a
                    >

                    <div class="mb-5">
                        <label for="movie-title" class="text-gray-300 block mb-2 font-bold"
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
                            If used, TMDB API will override all other data for this poster.
                        </div>
                    </div>

                    <hr class="border-grey-500 mb-5" />

                    <div class="mb-5">
                        <label for="movie-title" class="text-gray-300 block mb-2 font-bold"
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

                    <div class="mb-5">
                        <label for="movie-poster" class="text-gray-300 block mb-2 font-bold"
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

                    <div class="mb-5">
                        <label for="content-rating" class="text-gray-300 block mb-2 font-bold"
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
                        <label for="audience-rating" class="text-gray-300 block mb-2 font-bold"
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
                        <label for="trailer-path" class="text-gray-300 block mb-2 font-bold"
                            >YouTube Movie ID</label
                        >

                        <input
                            type="text"
                            class="text-black w-full"
                            id="trailer-path"
                            v-model="poster.trailer_path"
                        />
                    </div>

                    <div class="py-3 text-white">
                        {{ formMessage }}
                        <div v-for="error in errors">{{ error }}</div>
                    </div>

                    <button
                        class="btn text-black bg-gray-300 text-md px-3 py-1 hover:bg-gray-100"
                        @click.prevent="createPoster"
                        :disabled="saving"
                    >
                        {{ savePosterBtn }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import draggable from 'vuedraggable';

export default {
    data: function () {
        return {
            loading: false,
            saving: false,
            errors: [],
            posters: [],
            recaching: false,
            debounce: false,
            showPosterModal: false,
            formMessage: '',
            settings: {},
            poster: {
                id: '',
                imdb_id: '',
                name: '',
                image: null,
                mpaa_rating: '',
                audience_rating: '',
                trailer_path: '',
            },
            savePosterBtn: 'Save Poster',
        };
    },
    components: { draggable },
    watch: {},
    methods: {
        getPosters() {
            axios
                .get('/api/posters')
                .then((response) => {
                    this.posters = response.data.posters;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        cachePosters() {
            this.recaching = true;
            axios
                .get('/api/cache-posters')
                .then((response) => {
                    this.recaching = false;
                    this.posters = response.data.posters;
                })
                .catch((e) => {
                    this.recaching = false;
                    console.log(e.message);
                });
        },
        deletePoster(poster) {
            const params = {
                _method: 'delete',
            };
            axios
                .post('/api/posters/' + poster.id, params)
                .then((response) => {
                    this.posters.splice(this.posters.indexOf(poster), 1);
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        onDragStart(e) {
            console.log('call from `onDragStart` method', e);
        },
        onDragEnd(e) {
            var params = this.posters.map(function (item, index) {
                return { id: item.id, order: index };
            });

            if (this.debounce) return;

            this.debounce = setTimeout(
                function (params) {
                    this.debounce = false;
                    axios
                        .post('/api/posters-sort/', { items: params })
                        .then((response) => {
                            //this.posters response.data.posters;
                        })
                        .catch((e) => {
                            console.log(e.message);
                        });
                }.bind(this, params),
                1000
            );
        },
        updateShowInRotation(poster) {
            const params = {
                _method: 'put',
                show_in_rotation: poster.show_in_rotation,
            };
            axios
                .post('/api/posters/' + poster.id + '/update-rotation', params)
                .then((response) => {})
                .catch((e) => {});
        },
        selectFile() {
            this.poster.image = event.target.files[0];
        },
        editPoster(poster) {
            this.poster = Object.assign({}, poster);
            this.edit = true;
            this.showPosterModal = true;
        },
        createPoster() {
            this.errors = [];
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

            if (this.edit) {
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
                    if (!this.edit) {
                        this.posters.unshift(response.data.poster);
                    }

                    setTimeout(() => {
                        this.poster.name = '';
                        this.poster.image = null;
                        this.poster.imdb_id = '';
                        this.poster.id = '';
                        this.poster.mpaa_rating = '';
                        this.poster.audience_rating = '';
                        this.poster.trailer_path = '';
                        this.showPosterModal = false;
                        this.savePosterBtn = 'Save Poster';
                    }, 3000);
                })
                .catch((e) => {
                    console.log(e.message);
                    this.saving = false;
                    this.savePosterBtn = 'Save Poster';
                    this.errors = e.response.data.errors;
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
        this.getPosters();
    },
};
</script>

<style lang="scss">
.cache-overlay {
    position: absolute;
    top: 60px;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    padding: 48px;
    background-color: rgba(000, 000, 000, 0.75);
    align-items: center;
    justify-content: center;
    z-index: 2;

    span {
        font-size: 48px;
        letter-spacing: 1px;
        font-weight: 700;
        color: #fff;
        text-transform: uppercase;
    }
}

.handle {
    cursor: ns-resize;
}

.v-draggable__placeholder {
    background: #888;
}

.poster-form-model {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    background-color: rgba(000, 000, 000, 0.75);
    align-items: center;
    justify-content: center;
    display: hidden;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -2;

    &.show {
        display: flex;
        opacity: 1;
        z-index: 1000;
    }

    .inner {
        position: relative;
        padding: 24px;
        background-color: #444;

        .close {
            position: absolute;
            top: -6px;
            right: 12px;
            color: #fff;
            font-size: 32px;

            &:hover {
                color: #999;
            }
        }
    }
}
</style>
