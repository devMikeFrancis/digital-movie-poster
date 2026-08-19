<template>
    <div v-if="visible" class="voting-qr">
        <img v-if="dataUrl" :src="dataUrl" alt="Scan to vote" />
        <p class="voting-qr-caption">Scan to vote</p>
    </div>
</template>

<script>
import QRCode from 'qrcode';
import { io } from 'socket.io-client';

/**
 * Shows a QR code on the display while a voting session is open, so people in
 * the room can join from their phones.
 *
 * The code points at this device's own address rather than anything
 * configured: whatever host the display was opened on is, by definition, one
 * that reaches it on this network.
 */
export default {
    name: 'VotingQr',
    data() {
        return {
            socket: null,
            visible: false,
            dataUrl: '',
        };
    },
    computed: {
        voteUrl() {
            return window.location.origin + '/vote';
        },
    },
    methods: {
        async render() {
            if (this.dataUrl) {
                return;
            }

            try {
                this.dataUrl = await QRCode.toDataURL(this.voteUrl, {
                    width: 260,
                    margin: 1,
                    color: { dark: '#000000', light: '#ffffff' },
                });
            } catch (e) {
                // A display that cannot draw the code should still show posters.
                console.log('Could not render the voting QR code:', e.message);
            }
        },
    },
    mounted() {
        this.socket = io('http://' + window.location.hostname + ':3000');

        this.socket.on('session', (state) => {
            this.visible = !!state.votingEnabled;
            if (this.visible) {
                this.render();
            }
        });

        this.socket.on('voting:disabled', () => {
            this.visible = false;
        });
    },
    beforeUnmount() {
        if (this.socket) {
            this.socket.disconnect();
        }
    },
};
</script>

<style scoped lang="scss">
.voting-qr {
    position: absolute;
    top: 2vh;
    right: 2vh;
    z-index: 90;
    padding: 10px 10px 6px;
    border-radius: 8px;
    background-color: rgb(255 255 255 / 0.92);
    text-align: center;

    img {
        display: block;
        width: 13vh;
        height: 13vh;
    }
}

.voting-qr-caption {
    margin-top: 4px;
    color: #111;
    font-size: 1.4vh;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
</style>
