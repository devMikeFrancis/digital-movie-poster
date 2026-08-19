<template>
    <div class="speaker-config-bottom" v-if="settings.show_speaker_config">
        <span
            class="speaker-config speaker-config-bottom rounded-md px-1 border-3"
            :style="'border-color:' + settings.footer_text_color"
        >
            <span class="speaker-config-text">{{ settings.speaker_config }}</span>
        </span>
    </div>
</template>
<script>
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';

/**
 * The room's speaker layout, in the footer with the rest of what describes the
 * screen rather than the film.
 *
 * It could once sit at the top right instead, floating over the header. That
 * put it in the way of a header spread across the width, and the top of the
 * screen now carries only the header wording and the theatre name.
 */
export default {
    name: 'SpeakerConfig',
    computed: {
        ...mapState(usePostersStore, ['settings']),
    },
};
</script>
<style lang="scss" scoped>
.speaker-config {
    font-size: 2vw;
    font-weight: 400;
    line-height: 1;
}

.speaker-config-bottom {
    max-width: 7.5vw;
}
</style>
