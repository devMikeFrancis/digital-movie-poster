<template>
    <div class="voting-screen" v-if="active">
        <!-- Results: the round is over, so the code is no longer the point. -->
        <template v-if="showResults">
            <h1 class="voting-heading">{{ resultHeading }}</h1>

            <div class="voting-body">
                <div class="winners" v-if="winners.length">
                    <div class="winner" v-for="winner in winners" :key="winner.id">
                        <img :src="'/storage/posters/' + winner.file_name" :alt="winner.name" />
                        <span class="winner-votes">
                            {{ winner.votes }} vote<span v-if="winner.votes !== 1">s</span>
                        </span>
                    </div>
                </div>
            </div>

            <p class="voting-foot">Back to the posters in a moment…</p>
        </template>

        <!-- Open or running: the code is the point, so give it the room. -->
        <template v-else>
            <h1 class="voting-heading">Vote Now</h1>

            <div class="voting-body">
                <img class="voting-qr" v-if="dataUrl" :src="dataUrl" alt="Scan to vote" />
                <p class="voting-url">{{ voteUrl }}</p>
            </div>

            <div class="voting-foot">
                <template v-if="votingStarted">
                    <div class="countdown" :class="{ urgent: timer <= 5 }">{{ timer }}</div>
                    <p class="countdown-label">
                        {{ timer === 1 ? 'second left' : 'seconds left' }}
                    </p>
                </template>
                <template v-else>
                    <p class="waiting">
                        Scan the code to join.
                        <span v-if="voterCount">
                            {{ voterCount }} {{ voterCount === 1 ? 'person is' : 'people are' }} in.
                        </span>
                    </p>
                </template>
            </div>
        </template>
    </div>
</template>

<script>
import QRCode from 'qrcode';
import { io } from 'socket.io-client';

/**
 * Takes the whole display over while a vote is on.
 *
 * This used to be a small code in the corner of the slideshow, which asked
 * people to notice it between poster changes and then scan something the size
 * of a stamp across a room. A vote is the only thing worth looking at while it
 * is running, so it gets the screen: what to do, a code big enough to scan from
 * the sofa, and how long is left. The posters come back when the session
 * closes.
 */
export default {
    name: 'VotingScreen',
    emits: ['active'],
    data() {
        return {
            socket: null,
            votingEnabled: false,
            votingStarted: false,
            showResults: false,
            resultStatus: '',
            winners: [],
            voterCount: 0,
            timer: 0,
            countdown: null,
            pendingStart: null,
            dataUrl: '',
        };
    },
    computed: {
        active() {
            return this.votingEnabled || this.showResults;
        },
        voteUrl() {
            return window.location.origin + '/vote';
        },
        resultHeading() {
            if (this.resultStatus === 'tie') {
                return "It's a tie!";
            }

            if (this.resultStatus === 'winner') {
                return 'We have a winner!';
            }

            return 'Nobody voted.';
        },
    },
    watch: {
        // The dashboard listens for this to park the slideshow while a vote is
        // on, and to start it again afterwards.
        active(value) {
            this.$emit('active', value);
        },
    },
    methods: {
        async renderCode() {
            if (this.dataUrl) {
                return;
            }

            try {
                this.dataUrl = await QRCode.toDataURL(this.voteUrl, {
                    width: 900,
                    margin: 1,
                    color: { dark: '#000000', light: '#ffffff' },
                });
            } catch (e) {
                // A display that cannot draw the code should still show the
                // address underneath it, which is enough to join by hand.
                console.log('Could not render the voting QR code:', e.message);
            }
        },
        startCountdown(seconds) {
            this.stopCountdown();
            this.timer = seconds;
            // Matches the five second "Get Ready" the voters see.
            this.pendingStart = setTimeout(() => {
                this.pendingStart = null;
                this.tickFrom(this.timer);
            }, 5020);
        },
        resumeCountdown(seconds) {
            if (this.countdown || this.pendingStart) {
                return;
            }

            this.tickFrom(seconds);
        },
        tickFrom(seconds) {
            this.stopCountdown();
            this.timer = seconds;
            this.countdown = setInterval(() => {
                if (this.timer > 0) {
                    this.timer--;
                } else {
                    this.stopCountdown();
                }
            }, 1000);
        },
        stopCountdown() {
            clearInterval(this.countdown);
            clearTimeout(this.pendingStart);
            this.countdown = null;
            this.pendingStart = null;
        },
    },
    mounted() {
        this.socket = io('http://' + window.location.hostname + ':3000');

        this.socket.on('session', (state) => {
            this.votingEnabled = !!state.votingEnabled;
            this.votingStarted = !!state.votingStarted;
            this.voterCount = (state.users || []).length;

            if (this.votingEnabled) {
                this.renderCode();
            }

            // A display switched on mid-vote has missed start:voting, so take
            // the running clock from the session broadcast.
            if (state.votingStarted) {
                this.resumeCountdown(state.timer);
            } else {
                this.stopCountdown();
            }
        });

        this.socket.on('start:voting', (data) => {
            this.showResults = false;
            this.winners = [];
            this.votingStarted = true;
            this.startCountdown(data.timeLimit);
        });

        this.socket.on('end:voting', (data) => {
            this.stopCountdown();
            this.votingStarted = false;
            this.timer = 0;
            this.resultStatus = data.results.status;
            this.winners = data.results.winner || [];
            this.showResults = true;
        });

        this.socket.on('voting:disabled', () => {
            this.stopCountdown();
            this.votingEnabled = false;
            this.votingStarted = false;
            this.showResults = false;
            this.winners = [];
            this.timer = 0;
        });
    },
    beforeUnmount() {
        this.stopCountdown();
        if (this.socket) {
            this.socket.disconnect();
        }
    },
};
</script>

<style scoped lang="scss">
.voting-screen {
    position: fixed;
    inset: 0;
    z-index: 400;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 4vh 4vw;
    background-color: #000;
    text-align: center;
}

.voting-heading {
    color: #fff;
    font-size: 9vh;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.voting-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 0;
    gap: 1.5vh;
}

.voting-qr {
    width: auto;
    height: 100%;
    max-height: 52vh;
    aspect-ratio: 1;
    padding: 1.2vh;
    border-radius: 1.2vh;
    background-color: #fff;
}

.voting-url {
    color: #9ca3af;
    font-size: 2.2vh;
    letter-spacing: 0.04em;
}

.voting-foot {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #fff;
}

.countdown {
    font-size: 12vh;
    font-weight: 700;
    line-height: 1;
    transition: color 0.3s ease;
}

.countdown.urgent {
    color: #f87171;
}

.countdown-label {
    color: #9ca3af;
    font-size: 2.4vh;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.waiting {
    color: #d1d5db;
    font-size: 3.4vh;
}

.winners {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: center;
    gap: 3vh;
    height: 100%;
}

.winner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.4vh;
    height: 100%;

    img {
        min-height: 0;
        max-height: 52vh;
        width: auto;
        border-radius: 1vh;
        box-shadow: 0 1vh 3vh rgb(0 0 0 / 0.6);
    }
}

.winner-votes {
    color: #fff;
    font-size: 3vh;
    font-weight: 700;
}
</style>
