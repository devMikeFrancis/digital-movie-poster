import axios from 'axios';
import { usePostersStore } from '@/store/posters';

class Api {
    /**
     * Calls the Plex server directly from the browser.
     *
     * @param {string} route - The Plex resource path, e.g. '/library/sections'
     */
    apiCallPlex(route = '/') {
        const posterStore = usePostersStore();
        const baseUrl = 'http://' + posterStore.settings.plex_ip_address + ':32400';
        route += '?X-Plex-Token=' + posterStore.settings.plex_token;

        return axios({ url: route, baseURL: baseUrl });
    }
}

export default new Api();
