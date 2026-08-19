const { createServer } = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');

const PORT = Number(process.env.SOCKET_PORT || 3000);

// The previous build hardcoded a handful of hostnames, so reaching the Pi by
// its LAN IP was rejected by CORS. Default to allowing any origin (this server
// listens on the local network and holds no secrets) and let an operator pin
// it down with SOCKET_ALLOWED_ORIGINS if they want to.
const allowedOrigins = (process.env.SOCKET_ALLOWED_ORIGINS || '*')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean);

const redis = new Redis({
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: Number(process.env.REDIS_PORT || 6379),
    password: process.env.REDIS_PASSWORD || undefined,
});

redis.on('error', (err) => console.error('[dmp] redis error:', err.message));

const httpServer = createServer();
const io = new Server(httpServer, {
    cors: {
        origin: allowedOrigins.includes('*') ? true : allowedOrigins,
        methods: ['GET', 'POST'],
    },
});

let users = [];
let posters = [];

// A session is "enabled" once the admin opens it for joining. That is what the
// slideshow watches to decide whether to show its QR code.
let votingEnabled = false;
let votingStarted = false;

// How many posters each voter may pick. The admin sets it when opening a
// session; one keeps the old single-choice behaviour.
let maxSelections = 1;

let timerId = null;
let disableTimerId = null;
let timeLimit = 30;
let timer = 0;
let lastWinner = {};
let status = 'none';

// socket id -> array of poster ids that voter has chosen. Holding the whole
// selection per voter, rather than incrementing counters, means a reconnect or
// a dropped message cannot leave the tally drifting away from reality.
const selections = new Map();

// Results stay up for this long, then the session closes and the QR disappears.
const RESULTS_VISIBLE_MS = Number(process.env.VOTING_RESULTS_MS || 30000);

function tally() {
    const counts = new Map();

    for (const chosen of selections.values()) {
        for (const posterId of chosen) {
            counts.set(posterId, (counts.get(posterId) || 0) + 1);
        }
    }

    posters = posters.map((poster) => ({ ...poster, votes: counts.get(poster.id) || 0 }));
}

function sessionState() {
    return {
        votingEnabled,
        votingStarted,
        maxSelections,
        timeLimit,
        timer,
        status,
        posters,
        lastWinner,
        users,
    };
}

function broadcastSession() {
    io.emit('session', sessionState());
}

function closeSession() {
    clearTimeout(disableTimerId);
    disableTimerId = null;
    clearInterval(timerId);
    timerId = null;

    votingEnabled = false;
    votingStarted = false;
    posters = [];
    selections.clear();
    timer = 0;
    timeLimit = 30;
    maxSelections = 1;
    status = 'none';

    io.emit('voting:disabled', {});
    broadcastSession();
    console.log('[dmp] voting session closed');
}

function calcWinner() {
    tally();

    const maxVotes = posters.reduce((highest, poster) => Math.max(highest, poster.votes || 0), 0);
    const winner = maxVotes > 0 ? posters.filter((poster) => poster.votes === maxVotes) : [];

    if (winner.length === 1) {
        lastWinner = winner[0];
    }

    let winningStatus = 'winner';
    if (winner.length === 0) {
        winningStatus = 'nowinner';
    } else if (winner.length > 1) {
        winningStatus = 'tie';
    }

    votingStarted = false;
    status = 'done';

    io.emit('end:voting', {
        votingStarted: false,
        timer: 0,
        lastWinner,
        status: 'done',
        results: { status: winningStatus, winner },
    });
    broadcastSession();

    // Give everyone time to see the result, then close the session so the
    // slideshow stops advertising a vote that has finished.
    clearTimeout(disableTimerId);
    disableTimerId = setTimeout(closeSession, RESULTS_VISIBLE_MS);
}

function startTimer() {
    if (timer === 0) {
        clearInterval(timerId);
        timerId = null;
        calcWinner();
    } else {
        timer--;
    }
}

io.on('connection', (socket) => {
    socket.emit('users', { users });
    socket.emit('session', sessionState());

    socket.on('new:user', (data) => {
        users.push({ id: socket.id, name: data.name, voted: false });
        selections.set(socket.id, []);

        io.emit('users', { users });
        socket.emit('status', {
            votingStarted,
            timer,
            timeLimit,
            posters,
            lastWinner,
            status,
            maxSelections,
        });
        broadcastSession();
    });

    // The admin opens a session: voters can join and the slideshow shows the QR.
    socket.on('enable:voting', (data) => {
        clearTimeout(disableTimerId);
        disableTimerId = null;

        posters = (data.posters || []).map((poster) => ({ ...poster, votes: 0 }));
        maxSelections = Math.max(1, Number(data.maxSelections) || 1);
        timeLimit = Number(data.timeLimit) || 30;
        votingEnabled = true;
        votingStarted = false;
        status = 'open';
        selections.clear();
        users = users.map((user) => ({ ...user, voted: false }));

        broadcastSession();
        console.log('[dmp] voting session opened with', posters.length, 'posters, max', maxSelections, 'per voter');
    });

    socket.on('disable:voting', () => closeSession());

    socket.on('start:voting', (data) => {
        if (data && data.posters) {
            posters = data.posters.map((poster) => ({ ...poster, votes: 0 }));
        }
        if (data && data.maxSelections) {
            maxSelections = Math.max(1, Number(data.maxSelections) || 1);
        }

        timeLimit = Number((data && data.timeLimit) || timeLimit) || 30;
        timer = timeLimit;
        votingEnabled = true;
        votingStarted = true;
        status = 'inProgress';
        selections.clear();
        users = users.map((user) => ({ ...user, voted: false }));

        io.emit('start:voting', {
            posters,
            status,
            timeLimit,
            timer,
            votingStarted,
            maxSelections,
        });
        broadcastSession();

        // The clients run a five second "Get Ready" countdown first.
        setTimeout(() => {
            clearInterval(timerId);
            timerId = setInterval(startTimer, 1000);
        }, 5020);
    });

    // A voter's complete selection, capped server side so a modified client
    // cannot vote more times than the session allows.
    socket.on('set:votes', (data) => {
        if (!votingStarted) {
            return;
        }

        const chosen = Array.isArray(data && data.posterIds) ? data.posterIds : [];
        selections.set(socket.id, chosen.slice(0, maxSelections));

        users = users.map((user) =>
            user.id === socket.id ? { ...user, voted: selections.get(socket.id).length > 0 } : user
        );

        tally();

        io.emit('user:voted', { user_id: socket.id });
        io.emit('users', { users });
        broadcastSession();
    });

    socket.on('reset:voting', () => {
        users = users.map((user) => ({ ...user, voted: false }));
        selections.clear();
        tally();

        io.emit('voting:reset', { users });
        broadcastSession();
    });

    socket.on('dispatch:command', (data) => {
        io.emit('receive:command', data);
    });

    socket.on('disconnect', () => {
        users = users.filter((user) => user.id !== socket.id);
        selections.delete(socket.id);
        tally();

        io.emit('users', { users });
        broadcastSession();
    });
});

httpServer.listen(PORT, () => {
    console.log('[dmp] socket server listening on port ' + PORT);
});
