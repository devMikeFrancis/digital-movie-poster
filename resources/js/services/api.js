import { Service } from './service';
window.axios = require('axios');

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

class Api extends Service {
    constructor() {
        super();
    }

    /**
     * Makes calls to the api
     * @param {string} route - The api resource request, users, users/{id}
     * @param {object} params - Data passed to the resource, { name: 'Bob' }
     */
    apiCallPlex(route = '/', params = {}) {
        const baseUrl = 'http://' + localStorage.getItem('plexIpAddress') + ':32400';
        route += '?X-Plex-Token=' + localStorage.getItem('plexToken');
        return axios({ url: route, baseURL: baseUrl });
    }
}
export default new Api();
