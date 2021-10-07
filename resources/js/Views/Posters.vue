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
                                <router-link class="hover:text-gray-500 active" to="/posters"
                                    >Posters</router-link
                                >
                            </li>
                            <li class="mb-3">
                                <router-link class="hover:text-gray-500" to="/voting"
                                    >Voting</router-link
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
                                <a
                                    href="#"
                                    role="button"
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
                                    <span v-show="!recaching">Cache Posters</span>
                                    <span v-show="recaching">Caching Posters ...</span>
                                </a>
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
                                <div class="grid lg:grid-cols-12 gap-4 relative">
                                    <div class="sm:col-span-2 lg:col-span-1 w-12">
                                        <div
                                            class="poster-image-block handle"
                                            :style="
                                                'background-image: url(storage/posters/' +
                                                poster.file_name +
                                                ')'
                                            "
                                        ></div>
                                    </div>
                                    <div
                                        class="
                                            sm:col-span-2
                                            md:col-span-8
                                            lg:col-span-8
                                            flex
                                            items-center
                                        "
                                    >
                                        <router-link
                                            class="text-white font-bold"
                                            :to="'/posters/' + poster.id"
                                        >
                                            {{ poster.name }}</router-link
                                        >
                                    </div>

                                    <div
                                        class="
                                            sm:col-span-2
                                            md:col-span-1
                                            lg:col-span-1
                                            flex
                                            items-center
                                        "
                                    >
                                        <label class="text-white cursor-pointer"
                                            ><input
                                                type="checkbox"
                                                v-model="poster.show_in_rotation"
                                                class="hidden"
                                                @change="
                                                    updateSetting(
                                                        poster,
                                                        'show_in_rotation',
                                                        poster.show_in_rotation
                                                    )
                                                "
                                            />
                                            <span
                                                v-if="!poster.show_in_rotation"
                                                class="block opacity-60"
                                                ><img
                                                    src="/images/eye-slash-regular.svg"
                                                    alt="Not in rotation"
                                                    style="width: 24px; height: auto"
                                            /></span>
                                            <span v-if="poster.show_in_rotation"
                                                ><img
                                                    src="/images/eye-regular.svg"
                                                    alt="In rotation"
                                                    style="width: 24px; height: auto"
                                            /></span>
                                        </label>
                                    </div>

                                    <div
                                        class="
                                            sm:col-span-2
                                            md:col-span-1
                                            lg:col-span-1
                                            flex
                                            items-center
                                        "
                                    >
                                        <div class="relative">
                                            <!-- Dropdown toggle button -->
                                            <button
                                                @click.prevent="
                                                    poster.openOptions = !poster.openOptions
                                                "
                                                class="py-2 px-3 hover:bg-gray-500"
                                            >
                                                <span
                                                    ><img
                                                        src="/images/ellipsis-v-regular.svg"
                                                        alt="Options"
                                                        style="width: 8px; height: auto"
                                                /></span>
                                            </button>
                                            <!-- Dropdown list -->
                                            <div
                                                class="
                                                    absolute
                                                    right-0
                                                    py-2
                                                    mt-2
                                                    bg-white bg-gray-100
                                                    rounded-md
                                                    shadow-xl
                                                    w-44
                                                    z-20
                                                "
                                                v-bind:class="{ hidden: !poster.openOptions }"
                                                style="width: 240px"
                                            >
                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_trailer"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_trailer',
                                                                poster.show_trailer
                                                            )
                                                        "
                                                    />
                                                    Show trailer
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_dolby_atmos"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_dolby_atmos',
                                                                poster.show_dolby_atmos
                                                            )
                                                        "
                                                    />
                                                    Show Dolby Atmos Logo
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_dolby_51"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_dolby_51',
                                                                poster.show_dolby_51
                                                            )
                                                        "
                                                    />
                                                    Show Dolby 5.1 Logo
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_dolby_vision"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_dolby_vision',
                                                                poster.show_dolby_vision
                                                            )
                                                        "
                                                    />
                                                    Show Dolby Vision Logo
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_dtsx"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_dtsx',
                                                                poster.show_dtsx
                                                            )
                                                        "
                                                    />
                                                    Show DTS:X Logo
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_auro_3d"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_auro_3d',
                                                                poster.show_auro_3d
                                                            )
                                                        "
                                                    />
                                                    Show Auro 3D Logo
                                                </label>

                                                <label
                                                    class="
                                                        block
                                                        px-4
                                                        py-2
                                                        text-sm text-gray-700
                                                        hover:bg-gray-500
                                                        hover:text-white
                                                    "
                                                    ><input
                                                        type="checkbox"
                                                        v-model="poster.show_imax"
                                                        @change="
                                                            updateSetting(
                                                                poster,
                                                                'show_imax',
                                                                poster.show_imax
                                                            )
                                                        "
                                                    />
                                                    Show IMAX Enhanced Logo
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="
                                            sm:col-span-4
                                            lg:col-span-1
                                            flex
                                            items-center
                                            justify-end
                                            lg:pr-4
                                        "
                                    >
                                        <a
                                            href="#"
                                            @click.prevent="deletePoster(poster)"
                                            class="
                                                text-gray-300
                                                font-bold
                                                hover:text-gray-50
                                                absolute
                                                top-6
                                                right-6
                                                lg:relative
                                                lg:top-auto
                                                lg:right-auto
                                            "
                                            ><span v-if="poster.can_delete">[X]</span>
                                            <span v-if="!poster.can_delete">[X]</span></a
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
            // e
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
    },
    created() {},
    mounted() {
        this.getPosters();
    },
};
</script>

<style scoped lang="scss">
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

input[type='text'],
input[type='number'] {
    height: 32px;
    border-radius: 4px;
}

.handle {
    cursor: ns-resize;
}

.v-draggable__placeholder {
    background: #888;
}
</style>
