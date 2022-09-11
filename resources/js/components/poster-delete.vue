<template>
    <div class="relative" v-click-outside="close">
        <a
            href="#"
            @click.prevent="showDelete = true"
            class="text-gray-300 font-bold hover:opacity-40 transition-opacity"
            ><span>
                <svg
                    style="width: 18px; height: 18px"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 448 512"
                >
                    <path
                        style="fill: #fff"
                        d="M268 416h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12zM432 80h-82.41l-34-56.7A48 48 0 0 0 274.41 0H173.59a48 48 0 0 0-41.16 23.3L98.41 80H16A16 16 0 0 0 0 96v16a16 16 0 0 0 16 16h16v336a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128h16a16 16 0 0 0 16-16V96a16 16 0 0 0-16-16zM171.84 50.91A6 6 0 0 1 177 48h94a6 6 0 0 1 5.15 2.91L293.61 80H154.39zM368 464H80V128h288zm-212-48h24a12 12 0 0 0 12-12V188a12 12 0 0 0-12-12h-24a12 12 0 0 0-12 12v216a12 12 0 0 0 12 12z"
                    /></svg
            ></span>
        </a>
        <div
            class="
                w-52
                rounded-md
                bg-black
                text-white text-center
                absolute
                right-0
                -top-1
                z-20
                px-3
                py-1
                text-sm
            "
            v-if="showDelete"
        >
            Delete poster?
            <a href="#" class="underline" @click.pevent="deletePoster" role="button">Yes</a> or
            <a href="#" role="button" class="underline" @click.prevent="showDelete = false">No</a> ?
        </div>
    </div>
</template>

<script>
import { mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
import vClickOutside from 'click-outside-vue3';

export default {
    name: 'PosterDelete',
    props: {
        poster: Object,
    },
    directives: {
        clickOutside: vClickOutside.directive,
    },
    data: function () {
        return {
            showDelete: false,
        };
    },
    methods: {
        ...mapActions(usePostersStore, ['updateSetting']),
        deletePoster() {
            this.$emit('delete-poster', this.poster);
        },
        close() {
            this.showDelete = false;
        },
    },
    mounted() {},
};
</script>
