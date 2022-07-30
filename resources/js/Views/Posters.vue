<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-2">
                        <main-nav />
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
                            :list="posters"
                            handle=".handle"
                            group="posters"
                            @start="onDragStart"
                            @end="onDragEnd"
                            item-key="id"
                        >
                            <template #item="{ element }">
                                <div class="mb-5" style="background-color: #222">
                                    <div
                                        class="
                                            grid grid-cols-4
                                            md:grid-cols-12
                                            lg:grid-cols-12
                                            gap-4
                                            relative
                                        "
                                    >
                                        <div class="sm:col-span-1 lg:col-span-1 w-12">
                                            <div
                                                class="poster-image-block handle"
                                                :style="
                                                    'background-image: url(storage/posters/_tn_' +
                                                    element.file_name +
                                                    ')'
                                                "
                                            ></div>
                                        </div>
                                        <div
                                            class="
                                                col-span-1
                                                md:col-span-8
                                                lg:col-span-8
                                                flex
                                                items-center
                                            "
                                        >
                                            <router-link
                                                class="text-white font-bold"
                                                :to="'/posters/' + element.id"
                                            >
                                                {{ element.name }}</router-link
                                            >
                                        </div>

                                        <div
                                            class="
                                                col-span-1
                                                md:col-span-1
                                                lg:col-span-1
                                                flex
                                                items-center
                                                justify-center
                                            "
                                        >
                                            <label class="text-white cursor-pointer"
                                                ><input
                                                    type="checkbox"
                                                    v-model="element.show_in_rotation"
                                                    class="hidden"
                                                    @change="
                                                        updateSetting(
                                                            element,
                                                            'show_in_rotation',
                                                            element.show_in_rotation
                                                        )
                                                    "
                                                />
                                                <span
                                                    v-if="!element.show_in_rotation"
                                                    class="block opacity-60"
                                                    ><img
                                                        src="/images/eye-slash-regular.svg"
                                                        alt="Not in rotation"
                                                        style="width: 24px; height: auto"
                                                /></span>
                                                <span v-if="element.show_in_rotation"
                                                    ><img
                                                        src="/images/eye-regular.svg"
                                                        alt="In rotation"
                                                        style="width: 24px; height: auto"
                                                /></span>
                                            </label>
                                        </div>

                                        <div
                                            class="
                                                col-span-1
                                                md:col-span-1
                                                lg:col-span-1
                                                flex
                                                items-center
                                            "
                                        >
                                            <PosterOptions :poster="element" />
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
                                                @click.prevent="deletePoster(element)"
                                                class="
                                                    text-gray-300
                                                    font-bold
                                                    hover:text-gray-50
                                                    absolute
                                                    lg:relative
                                                    lg:top-auto
                                                    lg:right-auto
                                                    top-1
                                                    right-2
                                                    lg:top-auto
                                                    lg:right-2
                                                "
                                                ><span v-if="element.can_delete">[X]</span>
                                                <span v-if="!element.can_delete">[X]</span></a
                                            >
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MainNav from '../partials/MainNav.vue';
import PosterOptions from '../components/poster-options.vue';
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
            sockets: '',
        };
    },
    components: { MainNav, draggable, PosterOptions },
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
                        .post('/api/posters-sort', { items: params })
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

        reloadPosters() {
            this.socket.emit('dispatch:command', { command: 'reload' });
        },
    },
    created() {},
    mounted() {
        this.getPosters();
        if (typeof io !== 'undefined') {
            this.socket = io('http://' + import.meta.env.BASE_URL + ':3000');
        }
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

.poster-image-block {
    width: 100%;
    height: 60px;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
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
