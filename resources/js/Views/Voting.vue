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
        <div class="timer text-white" v-if="votingStarted">
            {{ timer }}
        </div>

        <div class="">
            <ul class="text-white" v-if="users.length">
                <li v-for="user in users">
                    {{ user.name }} <span v-if="myId === user.id"><-- ME</span>
                </li>
            </ul>
        </div>

        <div class="" v-if="!loggedin">
            <label>
                <input v-model="name" @keyup.enter="signIn()" placeholder="Your name" />
            </label>
            <button type="button" class="text-white" @click="signIn()">Sign in</button>
        </div>

        <div class="" v-if="!votingStarted && loggedin && status !== 'done'">
            <label class="text-white block">
                <input type="checkbox" v-model="random" /> Choose random posters
            </label>
            <div class="" v-if="random">
                <label class="text-white block">
                    <input
                        type="number"
                        pattern="[0-9]+"
                        class="text-black"
                        v-model="posterLimit"
                    />
                    Random limit
                </label>
            </div>
            <label class="text-white block">
                <input type="number" pattern="[0-9]+" class="text-black" v-model="timeLimit" />
                Voting Timer (in seconds)
            </label>
            <div class="posters-container" v-if="!random">
                <div v-for="poster in posters" class="">
                    <label class="text-white block">
                        <input
                            type="checkbox"
                            v-model="poster.checked"
                            @change="togglePoster($event, poster)"
                        />
                        <img
                            :src="'/storage/posters/' + poster.file_name"
                            class="mr-2"
                            style="max-width: 90px; height: auto"
                            :alt="poster.name"
                        />
                        {{ poster.name }}
                    </label>
                </div>
            </div>
            <div class="start-messages">
                <div v-for="startMessage in startMessages" class="start-message">
                    {{ startMessage }}
                </div>
            </div>
            <button type="button" class="text-white" @click="startVoting()">Start Voting</button>
        </div>

        <div class="vote-container" v-if="votingStarted">
            <div class="">
                <div v-for="vposter in votingOnPosters" class="">
                    <label>
                        <img
                            :src="'/storage/posters/' + vposter.file_name"
                            class="mr-2"
                            style="max-width: 90px; height: auto"
                            :alt="vposter.name"
                        />
                        <input
                            type="radio"
                            class="vote-toggle"
                            v-bind:value="vposter.id"
                            v-model="vote"
                            :disabled="timer === 0"
                        />
                    </label>
                </div>
            </div>
        </div>

        <div class="" v-if="showResults">
            <div class="result-message">We Have a {{ resultMessage }}!</div>
            <div class="">
                <div class="" v-for="winner in winners">
                    <span>{{ winner.votes }} votes</span>
                    <img
                        :src="'/storage/posters/' + winner.file_name"
                        style="max-width: 90px; height: auto"
                        :alt="winner.name"
                    />
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

            this.socket.on('status', (data) => {
                this.votingStarted = data.votingStarted;
                this.votingOnPosters = data.posters;
                this.timer = data.timer;
                this.lastWinner = data.lastWinner;
                this.status = data.status;
                if (this.status === 'inProgress') {
                    this.startTimer();
                }
            });

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
                console.log('VOTING OVER!', data);

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
            console.log(data);
            this.socket.emit('start:voting', data);
        },
        getRandomPosters() {
            let limit = parseInt(this.posterLimit);
            console.log(limit);
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
                //const toggle = e.target.checked ? 'add' : 'remove';
                const data = { new: newValue, old: oldValue };
                console.log(data);
                this.socket.emit('toggle:vote', data);
            } else {
                this.voteMessages.push('Sorry, time is up!');
            }
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
    background: #000;
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

.timer {
    width: 100px;
    padding: 6px 0;
    text-align: center;
    font-size: 36px;
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
