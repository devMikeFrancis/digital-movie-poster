<template>
    <div
        class="theater-name"
        :style="
            'background-color:' + settings.footer_bg_color + '; color:' + settings.header_text_color
        "
    >
        <span :style="nameFont">{{ settings.theater_name }}</span>
    </div>
</template>

<script>
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';

/**
 * The name of the room the display is in, above or below the poster.
 *
 * Borrows the header's font choice rather than adding another one to pick: on a
 * display where the header reads in a cinema face, a house name in the default
 * sans next to it looks like a mistake.
 */
export default {
    name: 'TheaterName',
    computed: {
        ...mapState(usePostersStore, ['settings']),
        nameFont() {
            const fonts = {
                'riemann-theater': 'Riemann Theatre',
                'great-attraction': 'Great Attraction',
                'midnight-champion': 'Midnight Champion',
                emerald: 'Emerald Grey',
                airstrike: 'Airstrike',
                'space-ranger': 'Space Ranger',
                'feast-flesh': 'Feast of Flesh BB',
                'camp-blood': 'CSNPWDT NFI',
                friday13: 'Friday13',
            };

            const font = fonts[this.settings.header_font];

            return font ? "font-family: '" + font + "'; font-weight: normal;" : '';
        },
    },
};
</script>

<style lang="scss" scoped>
.theater-name {
    width: 100%;
    padding: 1.2vh 2vw;
    text-align: center;
    font-size: 3.2vh;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.rotated {
    .theater-name {
        font-size: 2.8vh;
    }
}
</style>
