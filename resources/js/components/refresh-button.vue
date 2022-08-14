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
import { mapState, mapActions } from 'pinia';
import { usePostersStore } from '@/store/posters';
export default {
    name: 'RefreshButton',
    data: function () {
        return {
            btnLabel: 'Refresh Movie Posters',
        };
    },
    computed: {
        ...mapState(usePostersStore, ['loading', 'socket']),
    },
    methods: {
        ...mapActions(usePostersStore, ['boot', 'setSocket']),
        reloadPosters(e) {
            e.target.disabled = true;
            this.btnLabel = 'Refresh sent ...';
            this.socket.emit('dispatch:command', { command: 'reload' });
            setTimeout(() => {
                e.target.disabled = false;
                this.btnLabel = 'Refresh Movie Posters';
            }, 3000);
        },
    },
    mounted() {
        this.setSocket();
    },
};
</script>
