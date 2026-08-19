<template>
    <div class="vote-screen">
        <div class="vote-inner">
            <!-- Nothing running -->
            <div v-if="!votingEnabled && !showResults" class="text-center">
                <h1 class="text-white text-2xl font-bold mb-2">No vote running</h1>
                <p class="text-gray-400">
                    Nobody has opened a vote yet. Leave this page open — it will wake up on its
                    own when one starts.
                </p>
            </div>

            <!-- Join -->
            <div v-else-if="!joined" class="text-center">
                <h1 class="text-white text-2xl font-bold mb-2">Join the vote</h1>
                <p class="text-gray-400 mb-6">Pick a name so everyone can see who has voted.</p>

                <form class="flex justify-center gap-2" @submit.prevent="join">
                    <input
                        v-model="name"
                        type="text"
                        class="h-12 w-64 px-4 rounded-lg text-black"
                        placeholder="Your name…"
                        maxlength="30"
                        autofocus
                    />
                    <button
                        type="submit"
                        class="h-12 px-6 text-white rounded-lg bg-red-500 hover:bg-red-600"
                        :disabled="!name.trim()"
                    >
                        Join
                    </button>
                </form>
            </div>

            <!-- Waiting for the admin to start -->
            <div v-else-if="!votingStarted && !showResults" class="text-center">
                <h1 class="text-white text-2xl font-bold mb-2">You're in, {{ name }}</h1>
                <p class="text-gray-400 mb-6">Waiting for the vote to start…</p>
                <p v-if="voters.length" class="text-gray-500 text-sm">
                    {{ voters.length }} here: {{ voters.map((v) => v.name).join(', ') }}
                </p>
            </div>

            <!-- Voting -->
            <div v-else-if="votingStarted">
                <div class="flex items-baseline justify-between mb-4">
                    <h1 class="text-white text-xl font-bold">
                        {{ maxSelections === 1 ? 'Pick your favourite' : 'Pick up to ' + maxSelections }}
                    </h1>
                    <span class="text-white text-2xl font-bold">{{ timer }}</span>
                </div>

                <p class="text-gray-400 text-sm mb-4">
                    {{ chosen.length }} of {{ maxSelections }} selected.
                    <span v-if="chosen.length === maxSelections">
                        Tap one again to change your mind.
                    </span>
                </p>

                <div class="poster-grid">
                    <button
                        v-for="poster in posters"
                        :key="poster.id"
                        type="button"
                        class="poster-choice"
                        :class="{ chosen: chosen.includes(poster.id) }"
                        :disabled="timer === 0"
                        @click="toggle(poster.id)"
                    >
                        <img :src="'/storage/posters/' + poster.file_name" :alt="poster.name" />
                        <span class="tick" v-if="chosen.includes(poster.id)">✓</span>
                    </button>
                </div>
            </div>

            <!-- Results -->
            <div v-else-if="showResults" class="text-center">
                <h1 class="text-white text-2xl font-bold mb-4">{{ resultMessage }}</h1>
                <div class="poster-grid justify-center">
                    <div v-for="winner in winners" :key="winner.id" class="winner">
                        <img
                            :src="'/storage/posters/' + winner.file_name"
                            :alt="winner.name"
                        />
                        <span class="text-white text-sm">
                            {{ winner.votes }} vote<span v-if="winner.votes !== 1">s</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { io } from 'socket.io-client';

/**
 * The screen people reach by scanning the QR code on the display.
 *
 * Public on purpose: voters are guests on the network and requiring an account
 * would defeat the point. It holds no settings and can only cast votes into a
 * session the admin has opened.
 */
export default {
    name: 'Vote',
    data() {
        return {
            socket: null,
            name: '',
            joined: false,
            votingEnabled: false,
            votingStarted: false,
            maxSelections: 1,
            posters: [],
            voters: [],
            chosen: [],
            timer: 0,
            showResults: false,
            resultMessage: '',
            winners: [],
        };
    },
    methods: {
        connect() {
            this.socket = io('http://' + window.location.hostname + ':3000');

            this.socket.on('session', (state) => {
                this.votingEnabled = state.votingEnabled;
                this.votingStarted = state.votingStarted;
                this.maxSelections = state.maxSelections;
                this.voters = state.users || [];

                if (state.posters && state.posters.length) {
                    this.posters = state.posters;
                }

                if (!state.votingEnabled) {
                    // Session closed: drop back to the idle screen.
                    this.showResults = false;
                    this.chosen = [];
                    this.joined = false;
                }
            });

            this.socket.on('start:voting', (data) => {
                this.posters = data.posters;
                this.maxSelections = data.maxSelections || 1;
                this.votingStarted = true;
                this.showResults = false;
                this.chosen = [];
                this.startCountdown(data.timeLimit);
            });

            this.socket.on('end:voting', (data) => {
                this.votingStarted = false;
                this.timer = 0;
                this.winners = data.results.winner;
                this.showResults = true;
                this.resultMessage =
                    data.results.status === 'winner'
                        ? 'We have a winner!'
                        : data.results.status === 'tie'
                          ? "It's a tie!"
                          : 'Nobody voted.';
            });

            this.socket.on('voting:disabled', () => {
                this.showResults = false;
                this.joined = false;
                this.chosen = [];
            });
        },
        startCountdown(seconds) {
            clearInterval(this.countdown);
            // Matches the five second "Get Ready" the other screens show.
            this.timer = seconds;
            setTimeout(() => {
                this.countdown = setInterval(() => {
                    if (this.timer > 0) {
                        this.timer--;
                    } else {
                        clearInterval(this.countdown);
                    }
                }, 1000);
            }, 5020);
        },
        join() {
            if (!this.name.trim()) {
                return;
            }
            this.socket.emit('new:user', { name: this.name.trim() });
            this.joined = true;
        },
        toggle(posterId) {
            const at = this.chosen.indexOf(posterId);

            if (at > -1) {
                this.chosen.splice(at, 1);
            } else if (this.chosen.length < this.maxSelections) {
                this.chosen.push(posterId);
            } else if (this.maxSelections === 1) {
                // Single choice behaves like a radio: tapping another swaps it.
                this.chosen = [posterId];
            } else {
                return;
            }

            this.socket.emit('set:votes', { posterIds: this.chosen });
        },
    },
    mounted() {
        this.connect();
    },
    beforeUnmount() {
        clearInterval(this.countdown);
        if (this.socket) {
            this.socket.disconnect();
        }
    },
};
</script>

<style scoped lang="scss">
.vote-screen {
    min-height: 100vh;
    background-color: #121212;
    padding: 24px 16px;
}

.vote-inner {
    max-width: 720px;
    margin: 0 auto;
}

.poster-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.poster-choice {
    position: relative;
    flex: 1 1 140px;
    max-width: 200px;
    padding: 0;
    background: none;
    border: 3px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;

    img {
        display: block;
        width: 100%;
        border-radius: 5px;
    }

    &.chosen {
        border-color: #2563eb;
    }

    &:disabled {
        opacity: 0.6;
        cursor: default;
    }

    .tick {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 28px;
        height: 28px;
        line-height: 28px;
        text-align: center;
        border-radius: 50%;
        background-color: #2563eb;
        color: #fff;
        font-weight: bold;
    }
}

.winner {
    flex: 0 1 180px;
    text-align: center;

    img {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 6px;
    }
}
</style>
