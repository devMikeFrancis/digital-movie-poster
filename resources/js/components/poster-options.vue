<template>
    <div class="relative" v-click-outside="close">
        <!-- Dropdown toggle button -->
        <button
            @click.prevent="show = !show"
            class="py-2 px-2 hover:opacity-40 transition-opacity rounded-none"
            :class="{ 'bg-gray-500': show }"
        >
            <span
                ><img
                    src="/images/ellipsis-v-regular.svg"
                    alt="Options"
                    style="width: 8px; height: auto"
            /></span>
        </button>
        <!-- Dropdown list bottom-14 -->
        <div
            ref="dropdown"
            class="absolute right-0 py-2 mt-2 bg-white bg-gray-100 shadow-xl w-64 z-20"
            :class="{ hidden: !show }"
        >
            <div class="px-3 mb-2">
                <button
                    @click.prevent="$emit('editPoster', poster)"
                    class="bg-blue-700 text-white w-full py-2"
                >
                    Edit
                </button>
            </div>
            <label class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-500 hover:text-white"
                ><input
                    type="checkbox"
                    v-model="poster.show_in_rotation"
                    @change="updateSetting(poster, 'show_in_rotation', poster.show_in_rotation)"
                />
                Show in rotation
            </label>

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

            <label
                class="
                    block
                    px-4
                    py-2
                    text-sm text-gray-700
                    mb-3
                    hover:bg-gray-500
                    hover:text-white
                "
                ><input
                    type="checkbox"
                    v-model="poster.show_imax"
                    @change="updateSetting(poster, 'show_imax', poster.show_imax)"
                />
                Show IMAX Enhanced Logo
            </label>

            <div class="px-3">
                <button
                    v-if="!showDelete"
                    @click.prevent="showDelete = true"
                    class="bg-red-700 text-white w-full py-2"
                >
                    Delete
                </button>

                <div
                    class="w-full bg-black text-white text-center px-3 py-2 text-sm"
                    v-if="showDelete"
                >
                    Delete poster?
                    <a href="#" class="underline" @click.pevent="deletePoster" role="button">Yes</a>
                    or
                    <a href="#" role="button" class="underline" @click.prevent="showDelete = false"
                        >No</a
                    >
                    ?
                </div>
            </div>
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
            showDelete: false,
        };
    },
    watch: {
        show(newVal, oldVal) {
            setTimeout(() => {
                let out = this.isOutOfViewport();
                if (out.bottom) {
                    this.$refs.dropdown.style.bottom = '46px';
                } else {
                    this.$refs.dropdown.style.bottom = 'auto';
                }
            }, 10);
        },
    },
    computed: {},
    methods: {
        ...mapActions(usePostersStore, ['updateSetting']),
        isOutOfViewport() {
            let elem = this.$refs.dropdown;
            var bounding = elem.getBoundingClientRect();

            var out = {};
            out.top = bounding.top < 0;
            out.left = bounding.left < 0;
            out.bottom =
                bounding.bottom > (window.innerHeight || document.documentElement.clientHeight);
            out.right =
                bounding.right > (window.innerWidth || document.documentElement.clientWidth);
            out.any = out.top || out.left || out.bottom || out.right;
            out.all = out.top && out.left && out.bottom && out.right;

            return out;
        },
        deletePoster() {
            this.$emit('delete-poster', this.poster);
        },
        close() {
            this.show = false;
        },
    },
    mounted() {},
};
</script>
