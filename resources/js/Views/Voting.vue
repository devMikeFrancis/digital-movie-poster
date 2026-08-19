<template>
    <div class="admin py-5">
        <div class="md:container md:mx-auto lg:container lg:mx-auto">
            <div class="grid lg:grid-cols-12 gap-4">
                <div class="lg:col-span-3">
                    <main-nav />
                </div>

                <div class="lg:col-span-9 p-4" style="background-color: #121212">
                    <div class="voting-bar">
                        <ul class="tabs">
                            <li>
                                <a
                                    href="#setup"
                                    class="text-sm md:text-md"
                                    :class="{ active: tab === 'setup' }"
                                    @click.prevent="tab = 'setup'"
                                    >Setup</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#live"
                                    class="text-sm md:text-md"
                                    :class="{ active: tab === 'live' }"
                                    @click.prevent="tab = 'live'"
                                    >Live session</a
                                >
                            </li>
                        </ul>

                        <span class="session-state" :class="sessionStateClass">{{
                            sessionLabel
                        }}</span>
                    </div>

                    <!-- Setup: everything that has to be decided before people join. -->
                    <div v-show="tab === 'setup'" class="tab-panel">
                        <div class="panel">
                            <h3 class="panel-title">Posters in the running</h3>

                            <label class="text-white inline-flex items-center mb-4">
                                <input type="checkbox" v-model="random" :disabled="votingEnabled" />
                                <span class="ml-2">Pick them at random</span>
                            </label>

                            <div v-if="random" class="field">
                                <label class="field-label" for="poster-limit">How many</label>
                                <input
                                    id="poster-limit"
                                    type="number"
                                    min="1"
                                    :max="posters.length || 1"
                                    class="field-input w-24"
                                    v-model="posterLimit"
                                    :disabled="votingEnabled"
                                />
                                <p class="field-help">{{ posters.length }} posters to draw from.</p>
                            </div>

                            <div v-else>
                                <p class="field-help mb-3">
                                    Choose the posters people will vote on.
                                    <span class="text-gray-300">{{ chosenPosters.length }}</span>
                                    selected.
                                </p>
                                <div class="posters-container flex flex-wrap">
                                    <div v-for="poster in posters" class="choose-poster-item">
                                        <label class="text-white block">
                                            <input
                                                type="checkbox"
                                                v-model="poster.checked"
                                                :disabled="votingEnabled"
                                                @change="togglePoster($event, poster)"
                                            />
                                            <div>
                                                <img
                                                    :src="
                                                        '/storage/posters/_tn_' + poster.file_name
                                                    "
                                                    class="rounded-lg shadow-lg hover:shadow-none"
                                                    :alt="poster.name"
                                                />
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <h3 class="panel-title">Rules</h3>

                            <div class="fields">
                                <div class="field">
                                    <label class="field-label" for="time-limit">
                                        Voting timer
                                    </label>
                                    <input
                                        id="time-limit"
                                        type="number"
                                        min="5"
                                        class="field-input w-24"
                                        v-model="timeLimit"
                                        :disabled="votingStarted"
                                    />
                                    <p class="field-help">Seconds, once voting starts.</p>
                                </div>

                                <div class="field">
                                    <label class="field-label" for="max-selections">
                                        Picks per voter
                                    </label>
                                    <input
                                        id="max-selections"
                                        type="number"
                                        min="1"
                                        :max="selectionCap"
                                        class="field-input w-24"
                                        v-model="maxSelections"
                                        :disabled="votingEnabled"
                                    />
                                    <p class="field-help">
                                        Allowing as many picks as there are posters lets everyone
                                        vote for everything, which usually ends in a tie.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="panel-actions">
                            <div class="start-messages" v-if="startMessages.length">
                                <div v-for="startMessage in startMessages" class="start-message">
                                    {{ startMessage }}
                                </div>
                            </div>

                            <template v-if="!votingEnabled">
                                <button type="button" class="btn-primary" @click="openVoting()">
                                    Open for joining
                                </button>
                                <p class="field-help">
                                    Puts a QR code on the display so people can join from their
                                    phones. Voting starts when you say so.
                                </p>
                            </template>

                            <template v-else>
                                <button type="button" class="btn-plain" @click="tab = 'live'">
                                    Go to the live session
                                </button>
                                <p class="field-help">
                                    A session is already open. Close it to change any of this.
                                </p>
                            </template>
                        </div>
                    </div>

                    <!-- Live: running the session that is already open. -->
                    <div v-show="tab === 'live'" class="tab-panel">
                        <div class="panel" v-if="!votingEnabled && !showResults">
                            <h3 class="panel-title">No session open</h3>
                            <p class="field-help mb-4">
                                Choose your posters and rules first, then open the session for
                                joining.
                            </p>
                            <button type="button" class="btn-plain" @click="tab = 'setup'">
                                Go to setup
                            </button>
                        </div>

                        <template v-else>
                            <div class="panel">
                                <div class="stats">
                                    <div class="stat">
                                        <span class="stat-value">{{ users.length }}</span>
                                        <span class="stat-label">joined</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value">{{ votedCount }}</span>
                                        <span class="stat-label">voted</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value">{{ maxSelections }}</span>
                                        <span class="stat-label">picks each</span>
                                    </div>
                                    <div class="stat" v-if="votingStarted">
                                        <span class="stat-value">{{
                                            ready > 0 ? ready : timer
                                        }}</span>
                                        <span class="stat-label">{{
                                            ready > 0 ? 'get ready' : 'seconds left'
                                        }}</span>
                                    </div>
                                </div>

                                <p class="field-help" v-if="!votingStarted">
                                    A QR code is showing on the display, pointing at
                                    <span class="text-gray-300">{{ voteUrl }}</span
                                    >. People can scan it to join.
                                </p>
                            </div>

                            <div class="panel">
                                <h3 class="panel-title">Voters</h3>
                                <p class="field-help" v-if="!users.length">
                                    Nobody has joined yet.
                                </p>
                                <ul class="voters" v-else>
                                    <li v-for="user in users" class="voter">
                                        <span style="text-transform: capitalize">{{
                                            user.name
                                        }}</span>
                                        <span class="inline-block ml-2 user-voted" v-if="user.voted"
                                            ><svg
                                                aria-hidden="true"
                                                focusable="false"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 512 512"
                                            >
                                                <path
                                                    fill="currentColor"
                                                    d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"
                                                ></path></svg
                                        ></span>
                                    </li>
                                </ul>
                            </div>

                            <div class="panel" v-if="runningPosters.length">
                                <h3 class="panel-title">On the ballot</h3>
                                <div class="posters-container flex flex-wrap">
                                    <div
                                        v-for="poster in runningPosters"
                                        class="running-poster-item"
                                    >
                                        <img
                                            :src="'/storage/posters/_tn_' + poster.file_name"
                                            class="rounded-lg shadow-lg"
                                            :alt="poster.name"
                                        />
                                        <span class="running-poster-votes"
                                            >{{ poster.votes || 0 }} vote<span
                                                v-if="poster.votes !== 1"
                                                >s</span
                                            ></span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="panel-actions">
                                <div class="start-messages" v-if="startMessages.length">
                                    <div
                                        v-for="startMessage in startMessages"
                                        class="start-message"
                                    >
                                        {{ startMessage }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="btn-primary"
                                        :disabled="votingStarted"
                                        @click="startVoting()"
                                    >
                                        Start voting
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-plain"
                                        :disabled="votingStarted"
                                        @click="resetVoting()"
                                    >
                                        Reset votes
                                    </button>
                                    <button type="button" class="btn-plain" @click="closeVoting()">
                                        Close session
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="panel text-center" v-if="showResults">
                            <h3 class="text-white font-bold text-3xl mb-4">
                                {{ resultHeading }}
                            </h3>
                            <div
                                class="winner-container flex flex-wrap justify-center"
                                v-if="winners.length"
                            >
                                <div class="winner-item" v-for="winner in winners">
                                    <span class="votes"
                                        >{{ winner.votes }} vote<span v-if="winner.votes !== 1"
                                            >s</span
                                        ></span
                                    >
                                    <img
                                        :src="'/storage/posters/' + winner.file_name"
                                        :alt="winner.name"
                                        class="rounded-lg shadow-lg"
                                    />
                                </div>
                            </div>

                            <button type="button" class="btn-plain mt-6" @click="clearResults()">
                                Set up another vote
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { io } from 'socket.io-client';
import MainNav from '../partials/MainNav.vue';

/**
 * The admin console for a voting session. It is a control surface, not a
 * ballot: since the QR code went on the display, votes are cast on /vote from
 * people's phones, and this screen opens the session, starts it, and watches
 * the count come in.
 */
export default {
    components: {
        MainNav,
    },
    data: function () {
        return {
            tab: 'setup',
            startMessages: [],
            votingEnabled: false,
            votingStarted: false,
            maxSelections: 3,
            socket: '',
            users: [],
            posters: [],
            runningPosters: [],
            random: false,
            posterLimit: 3,
            chosenPosters: [],
            timeLimit: 30,
            timer: 0,
            ready: 0,
            status: 'none',
            readyInterval: null,
            timerInterval: null,
            showResults: false,
            resultStatus: '',
            winners: [],
        };
    },
    computed: {
        sessionLabel() {
            if (this.votingStarted) {
                return 'Voting in progress';
            }

            return this.votingEnabled ? 'Open for joining' : 'No session';
        },
        sessionStateClass() {
            if (this.votingStarted) {
                return 'live';
            }

            return this.votingEnabled ? 'on' : 'off';
        },
        selectionCap() {
            // Never offer more picks than there are posters to pick from.
            const inRunning = this.random
                ? parseInt(this.posterLimit) || 0
                : this.chosenPosters.length;

            return Math.max(1, inRunning || 1);
        },
        resultHeading() {
            if (this.resultStatus === 'tie') {
                return 'We Have a TIE!';
            }

            if (this.resultStatus === 'winner') {
                return 'We Have a WINNER!';
            }

            // Nobody picked anything before the timer ran out. Saying so beats
            // announcing a winner that does not exist.
            return 'Nobody voted.';
        },
        votedCount() {
            return this.users.filter((user) => user.voted).length;
        },
        voteUrl() {
            return window.location.origin + '/vote';
        },
    },
    watch: {
        // The cap moves as posters are picked or the random count changes, and
        // a max attribute only stops you typing past it - it will not pull a
        // number that is already too high back down.
        selectionCap(cap) {
            if (parseInt(this.maxSelections) > cap) {
                this.maxSelections = cap;
            }
        },
    },
    methods: {
        boot() {
            this.startSockets();
            this.getPosters();
        },
        disconnectSocket() {
            if (this.socket && typeof this.socket.disconnect === 'function') {
                this.socket.disconnect();
            }
            this.socket = '';
        },
        startSockets() {
            this.socket = io('http://' + location.hostname + ':3000');

            this.socket.on('session', (state) => {
                this.votingEnabled = state.votingEnabled;
                this.votingStarted = state.votingStarted;
                this.runningPosters = state.posters || [];

                // The session broadcast carries the whole voter list, so take
                // it from here rather than accumulating user:voted events: a
                // new session clears everyone's tick, and the incremental
                // handler alone would leave the last round's ticks showing.
                this.users = state.users || [];

                // Only adopt the server's figure once a session exists, or the
                // idle default would overwrite what the admin has typed.
                if (state.votingEnabled && state.maxSelections) {
                    this.maxSelections = state.maxSelections;
                }
            });

            this.socket.on('voting:disabled', () => {
                this.votingEnabled = false;
                this.votingStarted = false;
                this.runningPosters = [];
                this.timer = 0;
                this.ready = 0;
            });

            this.socket.on('users', (data) => {
                this.users = data.users;
            });

            this.socket.on('start:voting', (data) => {
                this.showResults = false;
                this.winners = [];
                this.ready = 5;
                this.votingStarted = data.votingStarted;
                this.runningPosters = data.posters || [];
                this.timer = data.timer;
                this.status = data.status;
                this.tab = 'live';

                if (this.status === 'inProgress') {
                    this.startReadyTimer();
                }
            });

            this.socket.on('end:voting', (data) => {
                this.resultStatus = data.results.status;
                this.winners = data.results.winner;
                this.votingStarted = data.votingStarted;
                this.timer = 0;
                this.ready = 0;
                this.status = data.status;
                this.showResults = true;
            });

            this.socket.on('user:voted', (data) => {
                this.users.forEach((user) => {
                    if (user.id === data.user_id) {
                        user.voted = true;
                    }
                });
            });

            this.socket.on('voting:reset', (data) => {
                this.users = data.users;
            });
        },
        getPosters() {
            axios
                .get('/api/posters')
                .then((response) => {
                    this.posters = response.data.posters;
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        togglePoster(e, poster) {
            if (e.target.checked) {
                this.chosenPosters.push(poster);
            } else {
                this.chosenPosters.splice(this.chosenPosters.indexOf(poster), 1);
            }
        },
        /**
         * Opens the session so people can join. This is what makes the QR code
         * appear on the display; voting itself still has to be started.
         */
        openVoting() {
            this.startMessages = [];

            const posters = this.postersForSession();
            if (!posters) {
                return false;
            }

            this.showResults = false;
            this.winners = [];

            this.socket.emit('enable:voting', {
                posters,
                maxSelections: Math.min(parseInt(this.maxSelections) || 1, posters.length),
                timeLimit: this.timeLimit,
            });

            this.tab = 'live';
        },
        closeVoting() {
            this.startMessages = [];
            this.socket.emit('disable:voting', {});
        },
        /**
         * The posters going into the running. Hand-picked means exactly what
         * was picked; the limit is how many to draw when going at random.
         */
        postersForSession() {
            if (!this.random) {
                if (this.chosenPosters.length === 0) {
                    this.startMessages.push('Please choose at least one poster.');
                    return null;
                }

                return this.chosenPosters.map((poster) => ({
                    ...poster,
                    votes: 0,
                }));
            }

            const limit = parseInt(this.posterLimit);

            if (!limit) {
                this.startMessages.push('Enter how many random posters to use.');
                return null;
            }

            if (limit > this.posters.length) {
                this.startMessages.push('You do not have that many posters.');
                return null;
            }

            return this.getRandomPosters().map((poster) => ({
                ...poster,
                votes: 0,
            }));
        },
        /**
         * Starts the session that is already open. The posters are whatever the
         * server published when it opened - re-deriving them here would draw a
         * fresh random set and change the ballot out from under everyone who
         * had already joined.
         */
        startVoting() {
            this.startMessages = [];

            if (!this.votingEnabled) {
                this.startMessages.push('Open a session for joining first.');
                return false;
            }

            this.showResults = false;
            this.winners = [];

            this.socket.emit('start:voting', {
                timeLimit: this.timeLimit,
                maxSelections: Math.min(
                    parseInt(this.maxSelections) || 1,
                    this.runningPosters.length || 1,
                ),
            });
        },
        getRandomPosters() {
            let limit = parseInt(this.posterLimit);

            var result = new Array(limit),
                len = this.posters.length,
                taken = new Array(len);

            while (limit--) {
                var x = Math.floor(Math.random() * len);
                result[limit] = this.posters[x in taken ? taken[x] : x];
                taken[x] = --len in taken ? taken[len] : len;
            }

            return result;
        },
        startReadyTimer() {
            clearInterval(this.readyInterval);
            this.readyInterval = setInterval(() => {
                if (this.ready === 1) {
                    this.startTimer();
                }
                if (this.ready === 0) {
                    clearInterval(this.readyInterval);
                } else {
                    this.ready--;
                }
            }, 1000);
        },
        startTimer() {
            clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                if (this.timer === 0) {
                    clearInterval(this.timerInterval);
                } else {
                    this.timer--;
                }
            }, 1000);
        },
        clearResults() {
            this.showResults = false;
            this.winners = [];
            this.resultStatus = '';
            this.tab = 'setup';
        },
        resetVoting() {
            this.socket.emit('reset:voting', {});
            this.showResults = false;
            this.winners = [];
            this.status = 'open';
            this.ready = 0;
            this.timer = 0;
        },
    },
    mounted() {
        this.boot();
    },
    beforeUnmount() {
        // Without this the socket outlives the screen: SPA navigation never
        // unloads the page, so the server keeps listing you as a voter and the
        // participant list fills with people who already left.
        clearInterval(this.readyInterval);
        clearInterval(this.timerInterval);
        this.disconnectSocket();
    },
};
</script>

<style scoped lang="scss">
/*
 * Tabs on the left, session state on the right, and stuck to the top: which
 * state the session is in decides what every control on the page does, so it
 * should stay readable while scrolling a long poster grid.
 */
.voting-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 20;
    background-color: #121212;
    padding: 8px 0;
    margin-bottom: 4px;
}

.tabs {
    display: flex;

    li {
        margin-right: 6px;

        &:last-child {
            margin-right: 0;
        }

        a {
            display: block;
            padding: 8px 0;
            min-width: 112px;
            text-align: center;
            color: #888;
            background-color: #333;

            &:hover {
                background-color: #777;
                color: #ccc;
                transition: background-color 0.25s ease;
            }

            &.active {
                color: #fff;
                background-color: #555;

                &:hover {
                    background-color: #777;
                }
            }
        }
    }
}

.session-state {
    font-size: 14px;
    padding: 6px 12px;
    border-radius: 3px;

    &.live {
        color: #fef08a;
        background-color: #713f12;
    }

    &.on {
        color: #bbf7d0;
        background-color: #14532d;
    }

    &.off {
        color: #9ca3af;
        background-color: #1f2937;
    }
}

.tab-panel {
    padding-top: 20px;
}

.panel {
    padding: 20px 24px;
    margin-bottom: 16px;
    background-color: #1c1c1c;
    border-radius: 4px;
}

.panel-title {
    margin-bottom: 16px;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.panel-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 0 24px 8px;
}

/*
 * Label above input above help text, so a number field and the sentence
 * explaining it stay together instead of drifting apart across a wide row.
 */
.fields {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
}

.field {
    max-width: 340px;
}

.field-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 700;
    color: #d1d5db;
}

.field-input {
    height: 42px;
    padding: 0 10px;
    color: #000;
    border-radius: 2px;
}

.field-help {
    margin-top: 6px;
    font-size: 14px;
    color: #9ca3af;
}

.btn-primary,
.btn-plain {
    height: 40px;
    padding: 0 16px;
    color: #fff;
    border-radius: 4px;

    &:disabled {
        color: #9ca3af;
        background-color: #374151;
        cursor: default;
    }
}

.btn-primary {
    background-color: #1d4ed8;

    &:hover:enabled {
        background-color: #2563eb;
    }
}

.btn-plain {
    background-color: #374151;

    &:hover:enabled {
        background-color: #4b5563;
    }
}

.start-messages {
    color: #fca5a5;
}

.voters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.voter {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    color: #fff;
    background-color: #2b2b2b;
    border-radius: 999px;
}

.stats {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    margin-bottom: 12px;
}

.stat {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 30px;
    font-weight: 700;
    line-height: 1.1;
    color: #fff;
}

.stat-label {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #9ca3af;
}

.posters-container {
    padding: 12px;
    background: #222;
    border-radius: 4px;
}

.user-voted {
    color: #4ade80;

    svg {
        width: 16px;
        height: 16px;
    }
}

.choose-poster-item {
    input {
        display: none;
    }

    input:checked + div {
        &:after {
            display: block;
            width: 24px;
            height: 24px;
            background-color: #000;
            background-image: url('/images/check.svg');
            background-size: 14px 14px;
            background-repeat: no-repeat;
            background-position: center;
            content: '';
            border-radius: 50%;
            border: 2px solid #fbbf24;
            position: absolute;
            right: -4px;
            top: -4px;
            z-index: 1;
            pointer-events: none;
        }
    }

    label {
        cursor: pointer;
        transition: opacity 0.25s ease;

        &:hover {
            opacity: 0.65;
        }
    }

    div {
        margin: 12px;
        position: relative;
        pointer-events: none;
    }

    img {
        max-width: 112px;
        height: auto;
    }
}

.running-poster-item {
    margin: 12px;
    text-align: center;

    img {
        max-width: 112px;
        height: auto;
    }
}

.running-poster-votes {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    color: #d1d5db;
}

.winner-container {
    padding: 12px;
    background: #222;
    border-radius: 4px;
}

.winner-item {
    margin: 12px;
    text-align: center;

    .votes {
        display: block;
        color: #fff;
        font-size: 24px;
        margin-bottom: 12px;
    }

    img {
        max-width: 300px;
        height: auto;
    }
}
</style>
