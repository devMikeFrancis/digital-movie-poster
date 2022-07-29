import axios from 'axios';
import io from 'socket.io-client';

window.axios = axios;
window.io = io;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

export { axios, io };
