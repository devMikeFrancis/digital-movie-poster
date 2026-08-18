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
let votingStarted = false;
let timerId = null;
let timeLimit = 30;
let timer = 0;
let lastWinner = {};
let status = 'none';

function calcWinner() {
    const maxVotes = Math.max.apply(
        Math,
        posters.map(function (o) {
            return o.votes;
        })
    );

    const winner = posters.filter(function (value, index, arr) {
        return value.votes === maxVotes;
    });

    if (winner.length === 1) {
        lastWinner = winner[0];
    }

    let winningStatus = winner.length > 1 ? 'tie' : 'winner';

    if (winner.length === 0) {
        winningStatus = 'nowinner';
    }

    /*
    users.forEach((v, i) => {
        users[i].voted = false;
    });*/

    io.emit('end:voting', {
        votingStarted: false,
        timer: 0,
        lastWinner: lastWinner,
        status: 'done',
        results: { status: winningStatus, winner: winner },
    });

    votingStarted = false;
    posters = [];
    timeLimit = 30;
    timer = 0;
    status = 'none';
}

function startTimer() {
    if (timer === 0) {
        clearInterval(timerId);
        calcWinner();
    } else {
        timer--;
    }
}

redis.psubscribe('*');
redis.on('pmessage', function (pattern, channel, message) {
    try {
        message = JSON.parse(message);
    } catch (err) {
        console.error('[dmp] ignoring unparseable message on', channel);
        return;
    }

    const eventName = message.event.replace(/\\/g, '');
    io.emit(eventName, message.data.data);
});

io.on('connection', (socket) => {
    socket.emit('users', { users: users });

    socket.on('new:user', (data) => {
        users.push({ id: socket.id, name: data.name, voted: false });
        io.emit('users', { users: users });
        socket.emit('status', {
            votingStarted: votingStarted,
            timer: timer,
            timeLimit: timeLimit,
            posters: posters,
            lastWinner: lastWinner,
            status: status,
        });
    });

    socket.on('start:voting', (data) => {
        posters = data.posters;
        votingStarted = true;
        timeLimit = data.timeLimit;
        timer = data.timeLimit;
        status = 'inProgress';
        io.emit('start:voting', {
            posters: data.posters,
            status: status,
            timeLimit: timeLimit,
            timer: timer,
            votingStarted: votingStarted,
            posters: posters,
        });
        setTimeout(() => {
            timerId = setInterval(startTimer, 1000);
        }, 5020);
    });

    socket.on('toggle:vote', (data) => {
        // Remove old vote of it exists
        if (data.old) {
            posters.forEach((v, i) => {
                if (v.id === data.old) {
                    posters[i].votes--;
                }
            });
        }
        // Assign vote
        posters.forEach((v, i) => {
            if (v.id === data.new) {
                posters[i].votes++;
            }
        });

        users.forEach((v, i) => {
            if (users[i].id === socket.id) {
                users[i].voted = true;
            }
        });

        io.emit('user:voted', {
            user_id: socket.id,
        });
    });

    socket.on('reset:voting', (data) => {
        users.forEach((v, i) => {
            users[i].voted = false;
        });

        io.emit('voting:reset', {
            users: users,
        });
    });

    socket.on('dispatch:command', (data) => {
        io.emit('receive:command', data);
    });

    socket.on('disconnect', () => {
        users = users.filter(function (value, index, arr) {
            return value.id !== socket.id;
        });

        io.emit('users', { users: users });

        if (users.length === 0) {
            votingStarted = false;
            posters = [];
            timeLimit = 30;
            timer = 0;
            status = 'start';
        }
    });
});

httpServer.listen(PORT, () => {
    console.log('[dmp] socket server listening on port ' + PORT);
});
