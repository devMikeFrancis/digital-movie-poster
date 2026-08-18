import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// The admin UI authenticates with a session cookie, and Laravel expects the
// matching XSRF token back on writes. Both are same-origin.
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

/*
 * Send the operator to the login screen when their session has lapsed.
 *
 * Never from "/" - that is the kiosk display, which is unauthenticated by
 * design and must not be navigated away from if a call happens to 401.
 */
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response && error.response.status;
        const url = (error.config && error.config.url) || '';
        const path = window.location.pathname;

        if (status === 401 && !url.includes('/api/auth/') && path !== '/' && path !== '/login') {
            window.location.assign('/login?redirect=' + encodeURIComponent(path));
        }

        return Promise.reject(error);
    }
);

export { axios };
