<template>
    <label class="text-white cursor-pointer hover:opacity-40 transition-opacity"
        ><input
            type="checkbox"
            v-model="poster.show_in_rotation"
            class="hidden"
            @change="updateSetting(poster, 'show_in_rotation', poster.show_in_rotation)"
        />
        <span v-if="!poster.show_in_rotation" class="block opacity-60"
            ><img
                src="/images/eye-slash-regular.svg"
                alt="Not in rotation"
                style="width: 24px; height: auto"
        /></span>
        <span v-if="poster.show_in_rotation"
            ><img src="/images/eye-regular.svg" alt="In rotation" style="width: 24px; height: auto"
        /></span>
    </label>
</template>
<script>
import { mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
export default {
    name: 'PosterInRotation',
    props: {
        poster: Object,
    },
    data: function () {
        return {};
    },
    methods: {
        ...mapActions(usePostersStore, ['updateSetting']),
    },
    mounted() {},
};
</script>
