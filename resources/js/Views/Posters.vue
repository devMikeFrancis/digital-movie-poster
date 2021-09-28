<template>
    <div>
        <div class="admin">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="pt-4 pb-4">
                    <router-link class="text-white" to="/">&larr; Back to DMP</router-link>
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
                                <router-link
                                    to="/posters/0"
                                    class="
                                        btn
                                        text-white
                                        bg-blue-600
                                        text-md
                                        px-4
                                        py-2
                                        hover:bg-blue-400
                                    "
                                >
                                    <span>&plus; Add Poster</span>
                                </router-link>
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
                                        <router-link
                                            class="text-white font-bold"
                                            :to="'/posters/' + poster.id"
                                        >
                                            {{ poster.name }}</router-link
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
</style>
