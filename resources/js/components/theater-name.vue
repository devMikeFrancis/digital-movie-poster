<template>
    <div class="theater-name" :class="'plate-' + plateStyle" :style="plateVars">
        <span class="plate">
            <span class="plate-text" :style="nameFont">{{ settings.theater_name }}</span>
        </span>
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
 * sans next to it looks like a mistake. The decoration is drawn in the header's
 * text colour for the same reason.
 */
export default {
    name: 'TheaterName',
    computed: {
        ...mapState(usePostersStore, ['settings']),
        plateStyle() {
            const styles = ['plain', 'rules', 'marquee', 'plaque', 'neon'];
            const chosen = this.settings.theater_name_style;

            return styles.includes(chosen) ? chosen : 'plain';
        },
        /**
         * The decorations are drawn with currentColor, so they only need the
         * text colour set once here rather than threaded through each rule.
         */
        plateVars() {
            return {
                color: this.settings.header_text_color,
                backgroundColor: this.settings.footer_bg_color,
            };
        },
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
    padding: 1.4vh 2vw;
    text-align: center;
    line-height: 1.1;
}

.plate {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 1.6vw;
    max-width: 90%;
}

.plate-text {
    font-size: 3.2vh;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    // The last letter's tracking would otherwise push the text off centre.
    text-indent: 0.14em;
}

/* Rules — hairlines either side, which reads as a caption rather than a label. */
.plate-rules .plate {
    width: 100%;
}

.plate-rules .plate::before,
.plate-rules .plate::after {
    content: '';
    flex: 1 1 auto;
    max-width: 16vw;
    height: 0.22vh;
    min-height: 1px;
    background-color: currentColor;
    opacity: 0.55;
}

/*
 * Marquee — a row of bulbs above and below, the one decoration that reads as a
 * cinema from across a room. Drawn rather than an image so it takes the text
 * colour and stays sharp at any size.
 */
.plate-marquee .plate {
    flex-direction: column;
    gap: 0.9vh;
    padding: 0 1.5vw;
}

.plate-marquee .plate::before,
.plate-marquee .plate::after {
    content: '';
    width: 100%;
    height: 1.1vh;
    background-image: radial-gradient(circle, currentColor 0 30%, transparent 34%);
    background-size: 2.4vh 1.1vh;
    background-repeat: repeat-x;
    background-position: center;
    opacity: 0.85;
}

/* Plaque — a bordered plate that hugs the name, like an engraved sign. */
.plate-plaque .plate {
    padding: 0.9vh 2.4vw;
    border: 0.28vh solid currentColor;
    border-radius: 0.5vh;
    box-shadow: inset 0 0 0 0.22vh rgb(255 255 255 / 0.12);
}

.plate-plaque .plate-text {
    font-size: 2.9vh;
}

/* Neon — a soft halo in the same colour, for a dark room. */
.plate-neon .plate-text {
    text-shadow:
        0 0 0.5vh currentColor,
        0 0 1.4vh currentColor,
        0 0 3vh currentColor;
}

.rotated {
    .plate-text {
        font-size: 2.8vh;
    }

    .plate-plaque .plate-text {
        font-size: 2.5vh;
    }
}
</style>
