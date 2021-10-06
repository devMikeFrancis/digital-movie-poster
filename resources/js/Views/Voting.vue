<template>
    <div>
        <div class="loading-overlay" v-if="loading">
            <div class="p-12">{{ loadingMessage }}</div>
        </div>
        <div
            class="get-ready-overlay"
            v-bind:class="{ show: status === 'inProgress' && ready > 0 }"
        >
            <div class="get-ready mb-1">Get Ready!</div>
            <div class="ready-time">{{ ready }}</div>
        </div>

        <div class="timer-container" v-if="votingStarted">
            <div class="timer text-white">
                {{ timer }}
            </div>
        </div>

        <div class="login-container" v-if="!loggedin">
            <div class="inner">
                <p class="text-2xl font-bold text-white text-center mb-3">
                    Enter your name to begin voting
                </p>
                <div class="relative flex justify-center items-center">
                    <input
                        type="text"
                        class="h-14 w-96 pl-8 pr-10 rounded-lg z-0 focus:shadow focus:outline-none"
                        placeholder="Your name..."
                        v-model="name"
                        autofocus
                        @keyup.enter="signIn()"
                        style="text-transform: capitalize"
                    />
                    <div class="absolute top-2 right-2">
                        <button
                            type="button"
                            class="h-10 w-20 text-white rounded-lg bg-red-500 hover:bg-red-600"
                            @click="signIn()"
                        >
                            Sign In
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ------ -->

        <div
            class="md:container md:mx-auto lg:container lg:mx-auto p-4"
            v-bind:class="{ 'pt-24': votingStarted }"
        >
            <div class="grid lg:grid-cols-12 gap-4">
                <div class="md:col-span-2">
                    <div v-if="users.length && loggedin">
                        <h3 class="text-white font-bold text-2xl mb-3">Voters</h3>
                        <ul class="block mb-8">
                            <li v-for="user in users" class="text-xl text-white">
                                <span
                                    v-bind:class="{ 'text-yellow-200': myId === user.id }"
                                    style="text-transform: capitalize"
                                    >{{ user.name }}</span
                                >
                            </li>
                        </ul>

                        <hr class="border-gray-700 mb-8" v-if="!votingStarted" />

                        <a
                            href="#"
                            class="text-white inline-block hover:text-gray-300"
                            @click.prevent="resetVoting()"
                            v-if="!votingStarted"
                            >Reset</a
                        >
                    </div>
                </div>

                <div class="md:col-span-10" v-if="!votingStarted && loggedin && status !== 'done'">
                    <div class="grid lg:grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-5 flex items-center">
                            <label class="text-white inline-flex items-center">
                                <input type="checkbox" v-model="random" />
                                <span class="ml-2">Randomize Posters</span>
                            </label>
                            <div class="" v-if="random">
                                <label class="text-white inline-flex items-center">
                                    <input
                                        type="number"
                                        pattern="[0-9]+"
                                        class="
                                            text-black
                                            h-10
                                            w-16
                                            px-2
                                            ml-3
                                            rounded-lg
                                            focus:shadow
                                            focus:outline-none
                                        "
                                        v-model="posterLimit"
                                    />
                                    <span class="ml-2">Random Limit</span>
                                </label>
                            </div>
                        </div>

                        <div class="md:col-span-5">
                            <label class="text-white inline-flex items-center">
                                <input
                                    type="number"
                                    pattern="[0-9]+"
                                    class="
                                        text-black
                                        h-10
                                        w-16
                                        px-2
                                        rounded-lg
                                        focus:shadow
                                        focus:outline-none
                                    "
                                    v-model="timeLimit"
                                />
                                <span class="ml-2"
                                    >Voting Timer <span class="text-sm">(seconds)</span></span
                                >
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <div class="start-messages">
                                <div
                                    v-for="startMessage in startMessages"
                                    class="start-message text-white"
                                >
                                    {{ startMessage }}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="
                                    h-10
                                    px-3
                                    text-white
                                    rounded-lg
                                    bg-blue-700
                                    hover:bg-blue-400
                                "
                                @click="startVoting()"
                            >
                                Start Voting
                            </button>
                        </div>
                    </div>

                    <div class="posters-container flex flex-wrap space-between" v-if="!random">
                        <div v-for="poster in posters" class="choose-poster-item">
                            <label class="text-white block">
                                <input
                                    type="checkbox"
                                    v-model="poster.checked"
                                    @change="togglePoster($event, poster)"
                                />
                                <div>
                                    <img
                                        :src="'/storage/posters/' + poster.file_name"
                                        class="rounded-lg shadow-lg hover:shadow-none"
                                        :alt="poster.name"
                                    />
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="vote-container md:col-span-10" v-if="votingStarted">
                    <div class="posters-container flex flex-wrap space-between">
                        <div v-for="vposter in votingOnPosters" class="vote-poster-item">
                            <label>
                                <input
                                    type="radio"
                                    class="vote-toggle"
                                    v-bind:value="vposter.id"
                                    v-model="vote"
                                    :disabled="timer === 0"
                                />
                                <div>
                                    <img
                                        :src="'/storage/posters/' + vposter.file_name"
                                        class="rounded-lg shadow-lg hover:shadow-none"
                                        :alt="vposter.name"
                                    />
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-12 md:col-span-10 text-center" v-if="showResults">
                    <h3 class="text-white font-bold text-3xl">We Have a {{ resultMessage }}!</h3>
                    <div class="winner-container flex flex-wrap justify-center">
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
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Api from '../services/api';
import EventEmitter from 'eventemitter3';

export default {
    data: function () {
        return {
            loading: false,
            loadingMessage: 'Loading',
            votingMessages: [],
            startMessages: [],
            loggedin: false,
            socket: '',
            users: [],
            name: '',
            posters: [],
            random: false,
            votingStarted: false,
            posterLimit: 3,
            chosenPosters: [],
            votingOnPosters: [],
            votes: [],
            timeLimit: 30,
            timer: 0,
            ready: 5,
            lastWinner: {},
            status: 'none',
            readyInterval: null,
            timerInterval: null,
            vote: '',
            showResults: false,
            resultMessage: '',
            winners: [],
        };
    },
    watch: {
        vote(newValue, oldValue) {
            if (this.votingStarted) {
                this.toggleVote(newValue, oldValue);
            }
        },
    },
    methods: {
        boot() {
            this.startSockets();
            this.getPosters();
        },
        signIn() {
            if (this.name.length > 2) {
                this.socket.emit('new:user', { name: this.name });
                this.loggedin = true;
            }
        },
        startSockets() {
            this.socket = io('http://localhost:3000');

            this.socket.on('connect', () => {
                this.myId = this.socket.id;
            });

            this.socket.on('users', (data) => {
                this.users = data.users;
            });

            /*
            this.socket.on('status', (data) => {
                this.votingStarted = data.votingStarted;
                this.votingOnPosters = data.posters;
                this.timer = data.timer;
                this.lastWinner = data.lastWinner;
                this.status = data.status;
                if (this.status === 'inProgress') {
                    this.startTimer();
                }
            });*/

            this.socket.on('start:voting', (data) => {
                this.votingStarted = data.votingStarted;
                this.votingOnPosters = data.posters;
                this.timer = data.timer;
                this.status = data.status;
                if (this.status === 'inProgress') {
                    this.startReadyTimer();
                }
            });

            this.socket.on('end:voting', (data) => {
                if (data.results.status === 'tie') {
                    this.resultMessage = 'TIE';
                }

                if (data.results.status === 'winner') {
                    this.resultMessage = 'WINNER';
                }

                this.winners = data.results.winner;

                this.votingStarted = data.votingStarted;
                this.votingOnPosters = [];
                this.votes = [];
                this.vote = '';
                this.random = false;
                this.posterLimit = 3;
                this.chosenPosters = [];
                this.timer = 0;
                this.ready = 5;
                this.status = data.status;
                this.showResults = true;
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
        startVoting() {
            this.startMessages = [];
            let posters = this.chosenPosters;

            if (!this.random && this.chosenPosters.length === 0) {
                this.startMessages.push('Please choose at least one poster.');
                return false;
            }

            if (this.random && this.posterLimit === 0) {
                this.startMessages.push('Enter a limit of random posters.');
                return false;
            }

            if (this.posterLimit > this.posters.length) {
                this.startMessages.push('Cannot choose an amount more than posters.');
                return false;
            }

            if (this.random) {
                posters = this.getRandomPosters();
            }

            const data = {
                posters: posters,
                timeLimit: this.timeLimit,
            };

            this.socket.emit('start:voting', data);
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

            result.forEach((v, i) => {
                result[i].votes = 0;
                result[i].checked = false;
            });

            return result;
        },
        startReadyTimer() {
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
            this.timerInterval = setInterval(() => {
                if (this.timer === 0) {
                    clearInterval(this.timerInterval);
                } else {
                    this.timer--;
                }
            }, 1000);
        },
        toggleVote(newValue, oldValue) {
            this.voteMessages = [];
            if (oldValue.length === 0) {
                oldValue = 0;
            }
            if (this.timer > 0) {
                const data = { new: newValue, old: oldValue };
                this.socket.emit('toggle:vote', data);
            } else {
                this.voteMessages.push('Sorry, time is up!');
            }
        },
        resetVoting() {
            this.votingStarted = false;
            this.votingOnPosters = [];
            this.votes = [];
            this.vote = '';
            this.status = 'none';
            this.winners = [];
            this.showResults = false;
            this.ready = 5;
            this.timer = 30;
            this.chosenPosters = [];
            this.posterLimit = 3;
            this.random = false;
        },
    },
    created() {},
    mounted() {
        this.boot();
    },
};
</script>

<style lang="scss">
body {
    background: #222;
}
.loading-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #fff;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    background-color: #000;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
}

.login-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #222;
    z-index: 500;

    .inner {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
}

.get-ready-overlay {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    background-color: #000;
    position: relative;
    width: 100vw;
    height: 100vh;
    z-index: 200;

    .get-ready {
        font-size: 46px;
        font-weight: 700;
    }

    .ready-time {
        font-size: 72px;
    }

    &.show {
        display: flex;
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
        max-width: 100px;
        height: auto;
    }
}

.vote-poster-item {
    input {
        display: none;
    }
    input:checked + div {
        &:after {
            display: block;
            width: 60px;
            height: 60px;
            background-color: #000;
            background-image: url('/images/thumbs-up.svg');
            background-size: 24px 24px;
            background-repeat: no-repeat;
            background-position: center;
            content: '';
            border-radius: 50%;
            border: 2px solid #fbbf24;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
        max-width: 200px;
        height: auto;
    }
}

.winner-item {
    margin: 12px;
    text-align: center;

    .votes {
        color: #fff;
        font-size: 24px;
        margin-bottom: 24px;
    }

    img {
        max-width: 300px;
        height: auto;
    }
}

.timer-container {
    display: flex;
    justify-content: center;
    background-color: #000;
    padding: 12px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 300;
}

.timer {
    width: 80px;
    padding: 6px 0;
    text-align: center;
    font-size: 28px;
    color: #fff;
    font-weight: 500;
    letter-spacing: 2px;
    border: 2px solid #fff;
    border-radius: 4px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 2s;
}
.fade-enter,
.fade-leave-to {
    opacity: 0;
}

.fade2-enter-active,
.fade2-leave-active {
    transition: opacity 1s;
}
.fade2-enter,
.fade2-leave-to {
    opacity: 0;
}

@keyframes FadeIn {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

@keyframes FadeOut {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}
</style>
