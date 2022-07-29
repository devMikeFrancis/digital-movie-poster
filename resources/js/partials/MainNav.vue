<template>
    <div class="sticky top-0 mb-5">
        <ul class="block text-gray-300 p-4 mb-5" style="background-color: #121212">
            <li class="mb-3">
                <a href="/" class="hover:text-gray-500">&larr; Back to DMP</a>
            </li>
            <li class="mb-3">
                <router-link class="hover:text-gray-500" to="/posters">Posters</router-link>
            </li>
            <li class="mb-3">
                <router-link class="hover:text-gray-500" to="/voting">Voting</router-link>
            </li>
            <li>
                <router-link class="hover:text-gray-500" to="/settings">Settings</router-link>
            </li>
        </ul>
        <refresh-button></refresh-button>
    </div>
</template>

<script>
import RefreshButton from '../components/refresh-button.vue';
export default {
    name: 'MainNav',
    components: {
        RefreshButton,
    },
};
</script>
