import { Service } from './service';
import { usePostersStore } from '@/store/posters';

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
        const posterStore = usePostersStore();
        const baseUrl = 'http://' + posterStore.settings.plex_ip_address + ':32400';
        route += '?X-Plex-Token=' + posterStore.settings.plex_token;
        return axios({ url: route, baseURL: baseUrl });
    }
}
export default new Api();
