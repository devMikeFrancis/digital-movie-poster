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

    // This process outlives any single Redis outage: the Pi may bring Redis up
    // after this service, and Redis may be restarted under it. The default of
    // 20 retries per request throws MaxRetriesPerRequestError from the command
    // queue, which is not something the 'error' handler below can catch - it
    // takes the whole process down, and with it voting and now-playing.
    maxRetriesPerRequest: null,
    retryStrategy: (attempt) => Math.min(attempt * 200, 5000),
});

redis.on('error', (err) => console.error('[dmp] redis error:', err.message));
redis.on('ready', () => console.log('[dmp] redis connected'));

process.on('unhandledRejection', (err) =>
    console.error('[dmp] unhandled rejection:', err && err.message ? err.message : err)
);

// This process is the display's voting and now-playing channel, and anything on
// the network can emit at it. A malformed payload reaching a handler used to
// throw synchronously and take the service down with it, which is a worse
// outcome than the bad message being ignored.
process.on('uncaughtException', (err) =>
    console.error('[dmp] uncaught exception:', err && err.stack ? err.stack : err)
);

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
let idleTimerId = null;
let timeLimit = 30;
let timer = 0;
let lastWinner = {};

// The finished round, kept past the end of the session so the setup screen and
// anyone still on the voting page can see who won last time. Cleared when a new
// round starts, not when the session closes.
let lastResult = null;

let status = 'none';

// voter id -> array of poster ids that voter has chosen. Holding the whole
// selection per voter, rather than incrementing counters, means a reconnect or
// a dropped message cannot leave the tally drifting away from reality.
//
// Keyed by voter id rather than socket id, and deliberately outliving the
// socket: people vote on a phone and then lock it or switch apps, and a
// backgrounded tab has its websocket closed. Dropping the selection with the
// connection threw those votes away and could hand the round to the wrong
// poster. The voter id is what makes that safe - it is a stable name for a
// ballot, so someone coming back updates the vote they already cast instead of
// casting a second one.
const selections = new Map();

// socket id -> voter id, so a disconnect knows whose ballot it belongs to.
const socketVoters = new Map();

// Results stay up for this long, then the session closes and the QR disappears.
// The winner is not lost at that point - it is kept as lastResult and shown as
// the previous session's winner until a new round starts.
const RESULTS_VISIBLE_MS = Number(process.env.VOTING_RESULTS_MS || 10000);

// A session that is opened for joining and then never started used to stay open
// for ever, leaving the QR code stranded on the display. Generous on purpose:
// an admin who opens the vote early and waits for the room to fill should not
// have it closed out from under them.
const IDLE_SESSION_MS = Number(process.env.VOTING_IDLE_MS || 600000);

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
        lastResult,
        users,
    };
}

function broadcastSession() {
    io.emit('session', sessionState());
}

function clearPendingCloses() {
    clearTimeout(disableTimerId);
    clearTimeout(idleTimerId);
    disableTimerId = null;
    idleTimerId = null;
}

function closeSession() {
    clearPendingCloses();
    clearInterval(timerId);
    timerId = null;

    votingEnabled = false;
    votingStarted = false;
    posters = [];
    selections.clear();

    // The session is over, so nobody is in it. Voters used to fall off this
    // list as their sockets closed, but a ballot now outlives its connection -
    // without clearing here, the finished session's voters lingered on the
    // console and made a closed session look like it was still running.
    users = [];

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
    lastResult = { status: winningStatus, winner };

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
    clearPendingCloses();
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

    // A default parameter only covers a missing payload, not one sent as null,
    // so each handler normalises what it was given.
    socket.on('new:user', (payload) => {
        const data = payload || {};
        const voterId = String(data.voterId || socket.id);
        socketVoters.set(socket.id, voterId);

        const existing = users.find((user) => user.id === voterId);

        if (existing) {
            // Same person returning - a reload, or a phone waking up. Keep the
            // ballot they already cast and put them back on the list.
            existing.name = data.name || existing.name;
            existing.connected = true;
        } else {
            users.push({ id: voterId, name: data.name, voted: false, connected: true });
        }

        if (!selections.has(voterId)) {
            selections.set(voterId, []);
        }

        // Hand back whatever this voter has already chosen so a page that
        // reloaded mid-round shows their picks rather than an empty ballot.
        socket.emit('your:votes', { posterIds: selections.get(voterId) });

        tally();
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
    socket.on('enable:voting', (payload) => {
        const data = payload || {};

        clearPendingCloses();

        // Nothing has been started yet, so put a ceiling on how long this can
        // sit there advertising itself.
        idleTimerId = setTimeout(closeSession, IDLE_SESSION_MS);

        // Anything on the network can emit this, so the list has to be one
        // before it is treated as one.
        posters = (Array.isArray(data.posters) ? data.posters : []).map((poster) => ({
            ...poster,
            votes: 0,
        }));
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

    // Ends the round now rather than waiting for the clock: counts what is in,
    // publishes the result, and lets the usual results window close the session.
    socket.on('end:voting:now', () => {
        if (!votingStarted) {
            return;
        }

        clearInterval(timerId);
        timerId = null;
        timer = 0;

        calcWinner();
        console.log('[dmp] voting ended early by the admin');
    });

    socket.on('start:voting', (data) => {
        // Starting again inside the results window would otherwise leave the
        // previous round's close timer pending, and it would shut the new
        // session down partway through. The idle timer goes too - the session
        // is plainly not idle any more.
        clearPendingCloses();

        if (data && Array.isArray(data.posters)) {
            posters = data.posters.map((poster) => ({ ...poster, votes: 0 }));
        }
        if (data && data.maxSelections) {
            maxSelections = Math.max(1, Number(data.maxSelections) || 1);
        }

        lastResult = null;
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
        const voterId = socketVoters.get(socket.id);

        if (!votingStarted || !voterId) {
            return;
        }

        const chosen = Array.isArray(data && data.posterIds) ? data.posterIds : [];
        selections.set(voterId, chosen.slice(0, maxSelections));

        users = users.map((user) =>
            user.id === voterId ? { ...user, voted: selections.get(voterId).length > 0 } : user
        );

        tally();

        io.emit('user:voted', { user_id: voterId });
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
        const voterId = socketVoters.get(socket.id);
        socketVoters.delete(socket.id);

        if (!voterId) {
            return;
        }

        // Their ballot stays in the tally for the rest of the round. Someone who
        // put their phone away has still voted, and closing the tab is not a way
        // to take a vote back.
        const stillOpen = [...socketVoters.values()].includes(voterId);

        if (!stillOpen) {
            users = users.map((user) =>
                user.id === voterId ? { ...user, connected: false } : user
            );
        }

        // A voter who never cast anything leaves no trace: keeping them would
        // pad the count with people who have gone.
        const chosen = selections.get(voterId);

        if (!stillOpen && (!chosen || chosen.length === 0)) {
            users = users.filter((user) => user.id !== voterId);
            selections.delete(voterId);
        }

        tally();
        io.emit('users', { users });
        broadcastSession();
    });
});

httpServer.listen(PORT, () => {
    console.log('[dmp] socket server listening on port ' + PORT);
});
