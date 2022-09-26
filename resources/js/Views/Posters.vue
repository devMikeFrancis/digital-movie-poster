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

                                <SyncButton
                                    :recaching="recaching"
                                    :syncInProcess="syncInProcess"
                                    @cachePosters="cachePosters"
                                />
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
                                        :class="{ 'not-in-rotation': !element.show_in_rotation }"
                                    >
                                        <div class="col-span-1 w-10 md:w-12">
                                            <div
                                                class="
                                                    poster-image-block
                                                    relative
                                                    handle
                                                    flex
                                                    items-center
                                                    justify-center
                                                    overflow-hidden
                                                "
                                                :style="
                                                    'background-image: url(storage/posters/_tn_' +
                                                    element.file_name +
                                                    ')'
                                                "
                                            >
                                                <span
                                                    class="
                                                        block
                                                        w-4
                                                        h-4
                                                        border border-white
                                                        rounded-full
                                                        bg-red-700
                                                    "
                                                    v-if="!element.show_in_rotation"
                                                ></span>
                                            </div>
                                        </div>
                                        <div class="col-span-5 md:col-span-10 flex items-center">
                                            <router-link
                                                class="
                                                    poster-title
                                                    block
                                                    py-1
                                                    font-bold
                                                    hover:text-gray-500
                                                "
                                                :class="{
                                                    'text-gray-400 line-through':
                                                        !element.show_in_rotation,
                                                    'text-white': element.show_in_rotation,
                                                }"
                                                :to="'/posters/' + element.id"
                                            >
                                                {{ element.title }}</router-link
                                            >
                                        </div>

                                        <div class="col-span-1 flex items-center justify-around">
                                            <div class="flex items-center justify-around">
                                                <PosterOptions
                                                    :poster="element"
                                                    @editPoster="editPoster"
                                                    @deletePoster="deletePoster"
                                                />
                                            </div>
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
import router from '@/router';
import { mapState, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';

import MainNav from '@/partials/MainNav.vue';
import PosterOptions from '@/components/poster-options.vue';
import PostersSkeleton from '@/components/posters-skeleton.vue';
import SyncButton from '@/components/sync-button.vue';
import SyncModal from '@/components/sync-modal.vue';
import draggable from 'vuedraggable';

export default {
    components: {
        MainNav,
        draggable,
        PosterOptions,
        PostersSkeleton,
        SyncButton,
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
        editPoster(poster) {
            router.push({ name: 'PostersEdit', params: { id: poster.id } });
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
                        if (this.syncInProcess) {
                            this.reloadPosters();
                        }

                        setTimeout(() => {
                            this.syncInProcess = false;
                            this.showSyncingModal = false;
                        }, 200);
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
            //this.reloadPosters();
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

.not-in-rotation {
    .poster-title {
    }
}

.handle {
    cursor: ns-resize;
}

.v-draggable__placeholder {
    background: #888;
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
