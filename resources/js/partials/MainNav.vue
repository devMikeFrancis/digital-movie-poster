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
        <button
            class="w-full text-white text-center py-2 px-1 rounded-md bg-gray-700 hover:bg-gray-500"
            type="button"
            @click.prevent="reloadPosters()"
        >
            Refresh Movie Posters
        </button>
    </div>
</template>

<script>
export default {
    name: 'MainNav',
    data: function () {
        return {
            socket: '',
        };
    },
    methods: {
        reloadPosters() {
            this.socket.emit('dispatch:command', { command: 'reload' });
        },
    },
    mounted() {
        if (typeof io !== 'undefined') {
            this.socket = io('http://movieposter.local:3000');
        }
    },
};
</script>
