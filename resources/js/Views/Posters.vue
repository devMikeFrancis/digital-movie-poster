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
                                    @click.prevent="showPosterModal = true"
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
                                    <div class="col-span-9 flex items-center">
                                        <span class="text-gray-300 font-bold">{{
                                            poster.name
                                        }}</span>
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
                    <a href="#" class="close" @click.prevent="showPosterModal = false">&times;</a>

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
                            If this is used the poster image and data will be pulled from TMDB API.
                        </div>
                    </div>

                    <hr class="border-grey-500 mb-5" />

                    <div class="mb-5">
                        <label for="movie-title" class="text-gray-300 block mb-2 font-bold"
                            >Movie Title</label
                        >

                        <input
                            type="text"
                            class="text-black w-full"
                            id="movie-title"
                            v-model="poster.title"
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
                            required
                        />
                        <div id="movie-posterHelp" class="text-gray-400 text-sm"></div>
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
                title: '',
                image: '',
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
        selectFile() {
            this.poster.image = event.target.files[0];
        },
        createPoster() {
            this.errors = [];
            this.saving = true;
            this.savePosterBtn = 'Saving ...';

            const error = this.validated();

            if (error) {
                return false;
            }

            const params = new FormData();
            params.append('image', this.poster.image);
            params.append('title', this.poster.title);
            params.append('imdb_id', this.poster.imdb_id);

            axios
                .post('/api/posters', params)
                .then((response) => {
                    this.posters.unshift(response.data.poster);
                    this.saving = false;
                    this.savePosterBtn = 'Saved Poster!';
                    this.poster.title = '';
                    this.poster.image = '';
                    this.poster.imdb_id = '';

                    setTimeout(() => {
                        this.showPosterModal = false;
                        this.savePosterBtn = 'Save Poster';
                    }, 3000);
                })
                .catch((e) => {
                    console.log(e.message);
                    this.saving = false;
                    this.savePosterBtn = 'Save Poster';

                    this.errors = e.data.errors;
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
        updateSettings() {
            /*this.settingsMessage = '';

            const params = {
                _method: 'put',
                plex_token: this.settings.plex_token,
                plex_ip_address: this.settings.plex_ip_address,
            };

            axios
                .post('/api/settings', params)
                .then((response) => {
                    this.settingsMessage = 'Settings saved.';
                })
                .catch((e) => {
                    this.settingsMessage = e.message;
                });*/
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
            top: -36px;
            right: -24px;
            color: #fff;
            font-size: 32px;

            &:hover {
                color: #999;
            }
        }
    }
}
</style>
