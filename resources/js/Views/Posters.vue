<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-3">
                        <main-nav />
                    </div>
                    <div class="lg:col-span-9 p-4 relative" style="background-color: #121212">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-4">
                                <router-link
                                    to="/posters/0"
                                    class="
                                        btn
                                        inline-block
                                        text-white text-center
                                        bg-blue-600
                                        text-md
                                        px-4
                                        py-1
                                        hover:bg-blue-400
                                        transition-colors
                                        w-full
                                        md:w-auto
                                    "
                                >
                                    <span>&plus; Add Poster</span>
                                </router-link>
                            </div>
                            <div
                                class="
                                    col-span-12
                                    md:col-span-8
                                    flex
                                    justify-between
                                    md:justify-end
                                    mb-4
                                "
                            >
                                <input
                                    type="text"
                                    class="search-input mr-5 w-40 md:w-52"
                                    v-model="searchQuery"
                                    placeholder="Search ..."
                                    :disabled="recaching"
                                />
                                <button
                                    class="
                                        btn
                                        text-black
                                        bg-gray-300
                                        text-md
                                        px-3
                                        py-1
                                        rounded-none
                                        hover:bg-gray-100
                                    "
                                    @click.prevent="cachePosters"
                                    :disabled="recaching || syncInProcess"
                                >
                                    <span v-show="!recaching && !syncInProcess"
                                        ><svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 512"
                                            class="mr-3"
                                        >
                                            <path
                                                d="M492 8h-10c-6.627 0-12 5.373-12 12v110.627C426.929 57.261 347.224 8 256 8 123.228 8 14.824 112.338 8.31 243.493 7.971 250.311 13.475 256 20.301 256h10.016c6.353 0 11.646-4.949 11.977-11.293C48.157 132.216 141.097 42 256 42c82.862 0 154.737 47.077 190.289 116H332c-6.627 0-12 5.373-12 12v10c0 6.627 5.373 12 12 12h160c6.627 0 12-5.373 12-12V20c0-6.627-5.373-12-12-12zm-.301 248h-10.015c-6.352 0-11.647 4.949-11.977 11.293C463.841 380.158 370.546 470 256 470c-82.608 0-154.672-46.952-190.299-116H180c6.627 0 12-5.373 12-12v-10c0-6.627-5.373-12-12-12H20c-6.627 0-12 5.373-12 12v160c0 6.627 5.373 12 12 12h10c6.627 0 12-5.373 12-12V381.373C85.071 454.739 164.777 504 256 504c132.773 0 241.176-104.338 247.69-235.493.339-6.818-5.165-12.507-11.991-12.507z"
                                            />
                                        </svg>
                                        Sync Posters</span
                                    >
                                    <span v-show="recaching || syncInProcess"
                                        ><svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 512"
                                            class="mr-3 spin"
                                        >
                                            <path
                                                d="M492 8h-10c-6.627 0-12 5.373-12 12v110.627C426.929 57.261 347.224 8 256 8 123.228 8 14.824 112.338 8.31 243.493 7.971 250.311 13.475 256 20.301 256h10.016c6.353 0 11.646-4.949 11.977-11.293C48.157 132.216 141.097 42 256 42c82.862 0 154.737 47.077 190.289 116H332c-6.627 0-12 5.373-12 12v10c0 6.627 5.373 12 12 12h160c6.627 0 12-5.373 12-12V20c0-6.627-5.373-12-12-12zm-.301 248h-10.015c-6.352 0-11.647 4.949-11.977 11.293C463.841 380.158 370.546 470 256 470c-82.608 0-154.672-46.952-190.299-116H180c6.627 0 12-5.373 12-12v-10c0-6.627-5.373-12-12-12H20c-6.627 0-12 5.373-12 12v160c0 6.627 5.373 12 12 12h10c6.627 0 12-5.373 12-12V381.373C85.071 454.739 164.777 504 256 504c132.773 0 241.176-104.338 247.69-235.493.339-6.818-5.165-12.507-11.991-12.507z"
                                            />
                                        </svg>
                                        Syncing Posters</span
                                    >
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-4"></div>
                            <div
                                class="
                                    col-span-12
                                    md:col-span-8
                                    flex
                                    justify-between
                                    md:justify-end
                                    mb-4
                                "
                            >
                                <label
                                    class="
                                        text-white
                                        cursor-pointer
                                        hover:opacity-40
                                        transition-opacity
                                        flex
                                        items-center
                                    "
                                >
                                    <span class="mr-2">Toggle all rotation</span>
                                    <input
                                        type="checkbox"
                                        v-model="all_show_in_rotation"
                                        class="hidden"
                                    />
                                    <span v-if="!all_show_in_rotation" class="block opacity-60"
                                        ><img
                                            src="/images/eye-slash-regular.svg"
                                            alt="Not in rotation"
                                            style="width: 24px; height: auto"
                                    /></span>
                                    <span v-if="all_show_in_rotation"
                                        ><img
                                            src="/images/eye-regular.svg"
                                            alt="In rotation"
                                            style="width: 24px; height: auto"
                                    /></span>
                                </label>
                                <button
                                    class="
                                        text-sm text-white
                                        border border-white
                                        hover:bg-gray-700
                                        px-2
                                        py-1
                                        ml-3
                                    "
                                    @click="setAllRotation"
                                >
                                    Set
                                </button>
                            </div>
                        </div>
                        <PostersSkeleton v-if="loading" />
                        <draggable
                            :list="filteredPosters"
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
                                            grid grid-cols-7
                                            md:grid-cols-12
                                            lg:grid-cols-12
                                            gap-4
                                            relative
                                        "
                                    >
                                        <div class="col-span-1 w-10 md:w-12">
                                            <div
                                                class="poster-image-block handle"
                                                :style="
                                                    'background-image: url(storage/posters/_tn_' +
                                                    element.file_name +
                                                    ')'
                                                "
                                            ></div>
                                        </div>
                                        <div class="col-span-4 md:col-span-7 flex items-center">
                                            <router-link
                                                class="
                                                    w-full
                                                    text-white
                                                    block
                                                    py-1
                                                    font-bold
                                                    hover:text-gray-500
                                                "
                                                :to="'/posters/' + element.id"
                                            >
                                                {{ element.title }}</router-link
                                            >
                                        </div>

                                        <div
                                            class="
                                                col-span-2
                                                md:col-span-4
                                                flex
                                                items-center
                                                justify-around
                                            "
                                        >
                                            <label
                                                class="
                                                    text-white
                                                    cursor-pointer
                                                    hover:opacity-40
                                                    transition-opacity
                                                "
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

                                            <PosterOptions :poster="element" />

                                            <PosterDelete
                                                :poster="element"
                                                @deletePoster="deletePoster"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <SyncModal @close="closeSyncModal" v-if="showSyncingModal" />
        </Transition>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';

import MainNav from '@/partials/MainNav.vue';
import PosterOptions from '@/components/poster-options.vue';
import PosterDelete from '@/components/poster-delete.vue';
import PostersSkeleton from '@/components/posters-skeleton.vue';
import SyncModal from '@/components/sync-modal.vue';
import draggable from 'vuedraggable';

export default {
    components: {
        MainNav,
        draggable,
        PosterOptions,
        PosterDelete,
        PostersSkeleton,
        SyncModal,
    },
    data: function () {
        return {
            showSyncingModal: false,
            loading: false,
            saving: false,
            errors: [],
            posters: [],
            recaching: false,
            debounce: false,
            showPosterModal: false,
            formMessage: '',
            searchQuery: '',
            fakePosters: [1, 2, 3, 4],
            syncInProcess: false,
            all_show_in_rotation: false,
        };
    },
    watch: {},
    computed: {
        ...mapState(usePostersStore, ['settings']),
        filteredPosters() {
            if (this.searchQuery.length > 0) {
                return this.posters.filter((poster) => {
                    return poster.name.replace(' ', '').includes(this.searchQuery);
                });
            } else {
                return this.posters;
            }
        },
    },
    methods: {
        ...mapActions(usePostersStore, [
            'boot',
            'controlTV',
            'setLoading',
            'updateSetting',
            'setSocket',
        ]),
        getPosters() {
            this.loading = true;
            axios
                .get('/api/posters')
                .then((response) => {
                    this.posters = response.data.posters;
                    this.loading = false;
                })
                .catch((e) => {
                    console.log(e.message);
                    this.loading = false;
                });
        },
        reloadPosters() {
            axios
                .get('/api/posters')
                .then((response) => {
                    this.posters = response.data.posters;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        setAllRotation() {
            axios
                .post('/api/show-in-rotation', { all_show_in_rotation: this.all_show_in_rotation })
                .then((response) => {
                    this.posters.forEach((el) => {
                        el.show_in_rotation = this.all_show_in_rotation;
                    });
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        cachePosters() {
            this.showSyncingModal = true;
            this.recaching = true;
            axios
                .get('/api/cache-posters')
                .then(() => {
                    this.recaching = false;
                    this.syncInProcess = true;
                })
                .catch((e) => {
                    this.recaching = false;
                    this.syncInProcess = false;
                    console.log(e.message);
                });
        },
        checkSyncStatus() {
            axios
                .get('/api/sync-status')
                .then((response) => {
                    if (response.data.status === 'running') {
                        this.syncInProcess = true;
                    } else {
                        this.syncInProcess = false;
                        this.showSyncingModal = false;
                    }
                })
                .catch((e) => {
                    this.syncInProcess = false;
                    console.log(e.message);
                });
        },
        closeSyncModal() {
            this.showSyncingModal = false;
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
            //
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
    },
    created() {},
    mounted() {
        this.getPosters();

        this.checkSyncStatus();
        setInterval(() => {
            this.checkSyncStatus();
            this.reloadPosters();
        }, 7000);
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
    align-items: top;
    justify-content: center;
    z-index: 2;

    span {
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
    height: 34px;
    border-radius: 2px;
    border: none;
    &:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
}

.btn {
    min-height: 34px;

    svg {
        width: 14px;
        height: 14px;
    }

    span {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
}

.search-input {
    background-color: #555;
    transition: background-color 0.25s ease;
    &::placeholder {
        color: #f4f4f4;
    }

    &:focus {
        background-color: #fff;
        &::placeholder {
            color: #333;
        }
    }

    &:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
}

.handle {
    cursor: ns-resize;
}

.v-draggable__placeholder {
    background: #888;
}

@-webkit-keyframes rotating /* Safari and Chrome */ {
    from {
        -webkit-transform: rotate(0deg);
        -o-transform: rotate(0deg);
        transform: rotate(0deg);
    }
    to {
        -webkit-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}
@keyframes rotating {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
.spin {
    animation: rotating 2s linear infinite;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
