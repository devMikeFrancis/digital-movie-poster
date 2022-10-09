<template>
    <div>
        <div v-for="(fakePoster, fIndex) in fakePosters" :key="'fake-' + fIndex">
            <div class="mb-5" style="background-color: #222">
                <div class="grid grid-cols-7 md:grid-cols-12 lg:grid-cols-12 gap-4">
                    <div class="col-span-1 w-10 md:w-12">
                        <div class="skeleton-poster-tn shimmer bg-gray-500 rounded-sm"></div>
                    </div>
                    <div class="col-span-5 md:col-span-10 flex items-center">
                        <div class="w-full h-4 bg-gray-500 rounded-sm shimmer"></div>
                    </div>
                    <div class="col-span-1 flex items-center justify-around">
                        <div class="w-4 h-5 bg-gray-500 rounded-sm shimmer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    components: {},
    data: function () {
        return {
            fakePosters: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        };
    },
    watch: {},
    computed: {},
    methods: {},
    created() {},
    mounted() {},
};
</script>

<style scoped lang="scss">
.skeleton-poster-tn {
    width: 48px;
    height: 60px;
}

.shimmer {
    position: relative;
    overflow: hidden;

    &::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background-image: linear-gradient(
            90deg,
            rgba(#fff, 0) 0,
            rgba(#fff, 0.2) 20%,
            rgba(#fff, 0.5) 60%,
            rgba(#fff, 0)
        );
        animation: shimmer 2s infinite;
        content: '';
    }
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}
</style>
