<template>
    <div
        :class="{ 'speaker-config-bottom': settings.speaker_config_location === 'bottom' }"
        v-if="settings.show_speaker_config"
    >
        <span
            class="speaker-config rounded-md px-1 border-3"
            :class="{
                'speaker-config-top': settings.speaker_config_location === 'top-right',
                'speaker-config-bottom': settings.speaker_config_location === 'bottom',
            }"
            :style="'border-color:' + color + '; text-color: ' + color"
        >
            <span class="speaker-config-text">{{ settings.speaker_config }}</span>
        </span>
    </div>
</template>
<script>
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';

export default {
    name: 'SpeakerConfig',
    data: function () {
        return {};
    },
    computed: {
        ...mapState(usePostersStore, ['settings']),
        color() {
            if (this.settings.speaker_config_location === 'bottom') {
                return this.settings.footer_text_color;
            }
            if (this.settings.speaker_config_location === 'top-right') {
                return this.settings.header_text_color;
            }
            return '#ffffff';
        },
    },
    methods: {},
    mounted() {},
};
</script>
<style lang="scss" scoped>
.speaker-config {
    font-size: 2vw;
    font-weight: 400;
    line-height: 1;
}
.speaker-config-top {
    position: absolute;
    right: 4%;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.8vw;
}

.speaker-config-bottom {
    max-width: 7.5vw;
}
</style>
