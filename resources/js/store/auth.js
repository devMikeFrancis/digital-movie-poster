import axios from 'axios';
import { defineStore } from 'pinia';

/**
 * Session state for the admin UI.
 *
 * The display at "/" never touches this - it polls endpoints that stay open
 * precisely because a kiosk browser has no way to sign in.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        required: true,
        authenticated: false,
        needsSetup: false,
        user: null,
        loaded: false,
    }),
    getters: {
        // True when the admin UI should be reachable without signing in.
        open: (state) => !state.required,
    },
    actions: {
        async loadStatus(force = false) {
            if (this.loaded && !force) {
                return;
            }

            try {
                const { data } = await axios.get('/api/auth/status');
                this.required = data.required;
                this.authenticated = data.authenticated;
                this.needsSetup = data.needs_setup;
                this.user = data.user;
            } catch (e) {
                // Treat an unreachable status endpoint as "locked" rather than
                // silently opening the admin UI up.
                this.authenticated = false;
            } finally {
                this.loaded = true;
            }
        },
        async login(credentials) {
            const { data } = await axios.post('/api/auth/login', credentials);
            this.authenticated = data.authenticated;
            this.user = data.user;
            this.needsSetup = false;
        },
        async setup(details) {
            const { data } = await axios.post('/api/auth/setup', details);
            this.authenticated = data.authenticated;
            this.user = data.user;
            this.needsSetup = false;
        },
        async logout() {
            try {
                await axios.post('/api/auth/logout');
            } finally {
                this.authenticated = false;
                this.user = null;
                // Full reload so every store drops whatever it had cached.
                window.location.assign('/login');
            }
        },
    },
});
