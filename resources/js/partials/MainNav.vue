<template>
    <div class="md:sticky top-0 px-4 md:px-0">
        <div class="relative">
            <div @click="showMenu = !showMenu" class="flex md:hidden justify-end">
                <button
                    type="button"
                    class="text-gray-500 hover:text-gray-300 focus:outline-none focus:text-gray-300"
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
                class="block text-gray-300 p-4 mb-5 mt-2 text-left"
                :class="showMenu ? 'block' : 'hidden'"
                style="background-color: #121212"
            >
                <li class="mb-3">
                    <a href="/" class="hover:text-gray-500">&larr; Back to DMP</a>
                </li>
                <li class="mb-3">
                    <router-link class="hover:text-gray-500" to="/posters">Posters</router-link>
                </li>
                <li class="mb-3">
                    <router-link class="hover:text-gray-500" to="/voting">Voting</router-link>
                </li>
                <li class="mb-5">
                    <router-link class="hover:text-gray-500" to="/settings">Settings</router-link>
                </li>
                <li>
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
            showMenu: false,
        };
    },
};
</script>
