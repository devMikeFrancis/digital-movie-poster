<template>
    <div>
        <button
            class="
                w-full
                text-white text-center
                py-2
                px-1
                rounded-none
                bg-gray-700
                hover:bg-gray-500
                disabled:cursor-wait
            "
            type="button"
            @click.prevent="reloadPosters($event)"
        >
            {{ btnLabel }}
        </button>
    </div>
</template>

<script>
export default {
    name: 'RefreshButton',
    data: function () {
        return {
            socket: '',
            btnLabel: 'Refresh Movie Posters',
        };
    },
    methods: {
        reloadPosters(e) {
            e.target.disabled = true;
            this.btnLabel = 'Refresh sent ...';
            if (this.socket.hasOwnProperty('emit')) {
                this.socket.emit('dispatch:command', { command: 'reload' });
            }
            setTimeout(() => {
                e.target.disabled = false;
                this.btnLabel = 'Refresh Movie Posters';
            }, 3000);
        },
    },
    mounted() {
        if (typeof io !== 'undefined') {
            this.socket = io('http://' + import.meta.env.VITE_BASE_URL + ':3000');
        }
    },
};
</script>
