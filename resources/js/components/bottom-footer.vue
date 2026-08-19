<template>
    <footer class="poster-footer" :style="'background-color:' + settings.footer_bg_color">
        <!--
            Everything here describes the poster on screen, so it changes with
            the poster rather than snapping to the next film while the artwork
            is still fading. The row is absolutely placed so the outgoing and
            incoming sets overlap instead of shunting each other sideways.
        -->
        <Transition :name="transitionName">
            <div class="footer-row" :key="posterKey">
                <ContentRating />
                <ProcessingLogos />

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
        ContentRating,
    },
    computed: {
        ...mapState(usePostersStore, [
            'settings',
            'nowPlaying',
            'rating',
            'audienceRating',
            'currentPosterId',
        ]),
        ...mapGetters(usePostersStore, ['transitionPrefix']),
        localRating() {
            return this.nowPlaying ? this.rating : this.audienceRating;
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

.fade-meta-enter-active,
.fade-meta-leave-active {
    transition: opacity 2.2s ease;
}

.fade-meta-enter-from,
.fade-meta-leave-to {
    opacity: 0;
}

.slide-meta-enter-active,
.slide-meta-leave-active {
    transition:
        transform 1.2s ease,
        opacity 1s ease;
}

.slide-meta-leave-to {
    transform: translate3d(0, -100%, 0);
    opacity: 0;
}

.slide-meta-enter-from {
    transform: translate3d(0, 100%, 0);
    opacity: 0;
}
.crossfade-meta-enter-active {
    transition: opacity 1.6s ease;
    z-index: 2;
}

.crossfade-meta-enter-from {
    opacity: 0;
}

.crossfade-meta-leave-active {
    transition: opacity 0.01s linear 1.6s;
    z-index: 1;
}

.crossfade-meta-leave-to {
    opacity: 0;
}

.cut-meta-enter-active,
.cut-meta-leave-active {
    transition: none;
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
    .audience-rating {
        width: 14vh;
    }
}
</style>
