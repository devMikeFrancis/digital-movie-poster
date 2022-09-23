<template>
    <div class="md:sticky top-0 px-4 md:px-0">
        <div class="relative">
            <div @click="showMenu = !showMenu" class="flex lg:hidden mb-2 justify-between">
                <h1 class="font-bold text-xl text-white flex items-center">
                    <img src="/favicon-96x96.png" width="52" height="52" alt="DMP" class="mr-2" />
                    Digital Movie Poster
                </h1>
                <button
                    type="button"
                    class="text-white hover:text-gray-300 focus:outline-none focus:text-gray-300"
                >
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                        <path
                            fill-rule="evenodd"
                            d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z"
                        ></path>
                    </svg>
                </button>
            </div>
            <ul
                class="block text-gray-300 mb-5 mt-2 md:mt-0 text-left"
                :class="menuVisible ? 'block' : 'hidden'"
                style="background-color: #121212"
            >
                <li class="px-2 py-4 mb-3 hidden lg:list-item" style="background-color: #000">
                    <a href="/"
                        ><h1 class="font-bold text-lg xl:text-xl text-white flex items-center">
                            <img
                                src="/favicon-96x96.png"
                                width="52"
                                height="52"
                                alt="DMP"
                                class="mr-2"
                            />
                            Digital Movie Poster
                        </h1>
                    </a>
                </li>
                <li class="lg:hidden py-2 px-4 pt-5 lg:pt-2 mb-3">
                    <a href="/"><strong>DMP</strong></a>
                </li>
                <li class="py-2 px-4 mb-3">
                    <router-link class="hover:text-gray-500" to="/posters">Posters</router-link>
                </li>
                <li class="py-2 px-4 mb-3">
                    <router-link class="hover:text-gray-500" to="/voting">Voting</router-link>
                </li>
                <li class="py-2 px-4 mb-5">
                    <router-link class="hover:text-gray-500" to="/settings">Settings</router-link>
                </li>
                <li class="pt-4" style="background-color: #000">
                    <refresh-button></refresh-button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import RefreshButton from '../components/refresh-button.vue';
export default {
    name: 'MainNav',
    components: {
        RefreshButton,
    },
    data() {
        return {
            windowWidth: 0,
            showMenu: false,
            mdBreakpoint: 1024,
        };
    },
    computed: {
        menuVisible() {
            return this.windowWidth > this.mdBreakpoint ? true : this.showMenu;
        },
    },
    methods: {
        updateWindowSize() {
            this.windowWidth = window.innerWidth;
        },
    },
    mounted() {
        this.updateWindowSize();
        window.addEventListener('resize', this.updateWindowSize);
    },
    beforeDestroyed() {
        window.removeEventListener('resize', this.updateWindowSize);
    },
};
</script>
