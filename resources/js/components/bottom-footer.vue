<template>
    <footer class="poster-footer" :style="'background-color:' + settings.footer_bg_color">
        <!--
            Everything here describes the poster on screen, so it changes with
            the poster rather than snapping to the next film while the artwork
            is still fading. The row is absolutely placed so the outgoing and
            incoming sets overlap instead of shunting each other sideways.
        -->
        <Transition :name="transitionName" mode="out-in">
            <div class="footer-row" :key="posterKey">
                <ContentRating />

                <span
                    class="runtime"
                    v-if="settings.show_runtime && localRuntime"
                    :style="'color:' + settings.footer_text_color"
                    >{{ localRuntime }} min</span
                >

                <ProcessingLogos />

                <!--
                    Not a processing logo, and it used to be rendered inside
                    that component - so switching the logos off took the speaker
                    badge with it, however the badge's own setting was left.
                -->
                <SpeakerConfig />

                <div class="audience-rating" v-if="settings.show_audience_rating">
                    <star-rating
                        :increment="0.1"
                        :max-rating="5"
                        :inactive-color="'#000'"
                        :active-color="settings.footer_text_color"
                        :star-size="starSize"
                        :rating="localRating"
                        :border-color="settings.footer_text_color"
                        :border-width="borderWidth"
                        :show-rating="false"
                        :read-only="true"
                    />
                </div>
            </div>
        </Transition>
    </footer>
</template>
<script>
import { mapGetters, mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';

import StarRating from 'vue-star-rating';
import ProcessingLogos from '@/components/processing-logos.vue';
import SpeakerConfig from '@/components/speaker-config.vue';
import ContentRating from '@/components/content-rating.vue';

export default {
    data: function () {
        return {
            borderWidth: 2,
            starSize: 28,
        };
    },
    components: {
        StarRating,
        ProcessingLogos,
        SpeakerConfig,
        ContentRating,
    },
    computed: {
        ...mapState(usePostersStore, [
            'settings',
            'nowPlaying',
            'rating',
            'audienceRating',
            'runtime',
            'nowPlayingRuntime',
            'currentPosterId',
        ]),
        ...mapGetters(usePostersStore, ['transitionPrefix']),
        localRating() {
            return this.nowPlaying ? this.rating : this.audienceRating;
        },
        localRuntime() {
            const rt = this.nowPlaying ? this.nowPlayingRuntime : this.runtime;

            return rt ? rt.toFixed(0) : false;
        },
        transitionName() {
            return this.transitionPrefix + '-meta';
        },
        posterKey() {
            return this.nowPlaying ? 'now-playing' : this.currentPosterId;
        },
    },
    methods: {
        setStarSizes() {
            const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
            if (vw > 2000) {
                this.starSize = 30;
            }
            if (vw > 3000) {
                this.starSize = 40;
                this.borderWidth = 4;
            }
            if (vw <= 2000) {
                this.starSize = 22;
            }
            if (vw <= 1200) {
                this.starSize = 20;
            }
            if (vw <= 900) {
                this.starSize = 18;
            }
            if (vw <= 700) {
                this.starSize = 10;
            }
        },
    },
    created() {
        this.setStarSizes();
    },
    mounted() {
        window.addEventListener('resize', this.setStarSizes);
    },
};
</script>
<style lang="scss" scoped>
.poster-footer {
    width: 100%;
    min-height: 14vh;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    padding: 2vw;
    position: relative;
    overflow: hidden;
}

.footer-row {
    position: absolute;
    inset: 2vw;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
}

/*
 * The details swap rather than overlap. The poster's cross-fade holds the
 * outgoing image at full opacity while the incoming one covers it, which works
 * because a poster covers a poster. These are centred text and icons of
 * different widths, so nothing covers anything and both sets were readable at
 * once. With out-in below, the old details leave before the new ones arrive,
 * and the whole swap still fits inside the poster's change.
 */
.fade-meta-enter-active,
.fade-meta-leave-active,
.crossfade-meta-enter-active,
.crossfade-meta-leave-active {
    transition: opacity 0.6s ease;
}

.fade-meta-enter-from,
.fade-meta-leave-to,
.crossfade-meta-enter-from,
.crossfade-meta-leave-to {
    opacity: 0;
}

.slide-meta-enter-active,
.slide-meta-leave-active {
    transition:
        transform 0.5s ease,
        opacity 0.5s ease;
}

.slide-meta-leave-to {
    transform: translate3d(0, -60%, 0);
    opacity: 0;
}

.slide-meta-enter-from {
    transform: translate3d(0, 60%, 0);
    opacity: 0;
}

.cut-meta-enter-active,
.cut-meta-leave-active {
    transition: none;
}
/*
 * Sits beside the content rating at the left of the row, and rides the row's
 * own swap - it used to be positioned over the header with a Transition of its
 * own to do the same job.
 */
.runtime {
    font-size: 2vw;
    font-weight: 400;
    line-height: 1;
    white-space: nowrap;
    margin-left: 1.5vw;
}

.audience-rating {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
    width: 11vh;
    margin-left: auto;
}
.vue-star-rating {
    margin-top: -4px;
}

.vue-star-rating-star {
    margin-right: 4px !important;
}

.rotated {
    .poster-footer {
        min-height: 13.5vh;
        padding: 2.8vh;
    }
    /*
 * Sits beside the content rating at the left of the row, and rides the row's
 * own swap - it used to be positioned over the header with a Transition of its
 * own to do the same job.
 */
    .runtime {
        font-size: 2vw;
        font-weight: 400;
        line-height: 1;
        white-space: nowrap;
        margin-left: 1.5vw;
    }

    .audience-rating {
        width: 14vh;
    }
}
</style>
