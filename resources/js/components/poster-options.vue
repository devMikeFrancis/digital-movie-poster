<template>
    <div class="relative" v-click-outside="close">
        <!-- Dropdown toggle button -->
        <button
            @click.prevent="show = !show"
            class="py-2 px-4 hover:opacity-40 transition-opacity rounded-none"
            :class="{ 'bg-gray-500': show }"
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
            class="absolute right-0 py-2 mt-2 bg-white bg-gray-100 shadow-xl w-44 z-20"
            v-bind:class="{ hidden: !show }"
            style="width: 240px"
        >
            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_trailer"
                    @change="updateSetting(poster, 'show_trailer', poster.show_trailer)"
                />
                Show trailer
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_dolby_atmos"
                    @change="updateSetting(poster, 'show_dolby_atmos', poster.show_dolby_atmos)"
                />
                Show Dolby Atmos Logo
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_dolby_51"
                    @change="updateSetting(poster, 'show_dolby_51', poster.show_dolby_51)"
                />
                Show Dolby 5.1 Logo
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_dolby_vision"
                    @change="updateSetting(poster, 'show_dolby_vision', poster.show_dolby_vision)"
                />
                Show Dolby Vision Logo
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_dtsx"
                    @change="updateSetting(poster, 'show_dtsx', poster.show_dtsx)"
                />
                Show DTS:X Logo
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_auro_3d"
                    @change="updateSetting(poster, 'show_auro_3d', poster.show_auro_3d)"
                />
                Show Auro 3D Logo
            </label>

            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_imax"
                    @change="updateSetting(poster, 'show_imax', poster.show_imax)"
                />
                Show IMAX Enhanced Logo
            </label>
        </div>
    </div>
</template>

<script>
import { mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
import vClickOutside from 'click-outside-vue3';

export default {
    name: 'PosterOptions',
    props: {
        poster: Object,
    },
    directives: {
        clickOutside: vClickOutside.directive,
    },
    data: function () {
        return {
            show: false,
        };
    },
    methods: {
        ...mapActions(usePostersStore, ['updateSetting']),
        close() {
            this.show = false;
        },
    },
    mounted() {},
};
</script>
