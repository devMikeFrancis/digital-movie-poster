import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import Dashboard from '../Views/Dashboard.vue';
import Settings from '../Views/Settings.vue';
import Posters from '../Views/Posters.vue';
import PostersEdit from '../Views/PostersEdit.vue';
import Voting from '../Views/Voting.vue';
import About from '../Views/About.vue';
import Login from '../Views/Login.vue';
import Vote from '../Views/Vote.vue';

const routes = [
    {
        // The kiosk display. Deliberately public: the Pi's browser boots
        // straight into this and has no way to sign in.
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: {
            requiresSetup: true,
        },
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/settings',
        name: 'Settings',
        component: Settings,
        meta: { requiresAuth: true },
    },
    {
        path: '/posters',
        name: 'Posters',
        component: Posters,
        meta: { requiresAuth: true },
    },
    {
        path: '/posters/:id',
        name: 'PostersEdit',
        component: PostersEdit,
        meta: { requiresAuth: true },
    },
    {
        // Public on purpose: this is what the QR code on the display points at,
        // and voters are guests who have no account. It can only cast votes
        // into a session an admin has opened.
        path: '/vote',
        name: 'Vote',
        component: Vote,
    },
    {
        path: '/voting',
        name: 'Voting',
        component: Voting,
        meta: { requiresAuth: true },
    },
    {
        path: '/about',
        name: 'About',
        component: About,
        meta: { requiresAuth: true },
    },
];

let router = createRouter({
    history: createWebHistory('/'),
    linkExactActiveClass: 'active',
    routes,
});

router.beforeEach(async (to, from, next) => {
    if (from.path === '/' && from.name !== null) {
        clearInterval(window.transitionImagesInterval);
        if (window.audio) {
            window.audio.pause();
            window.audio = null;
        }
    }

    if (!to.meta.requiresAuth) {
        return next();
    }

    const auth = useAuthStore();
    await auth.loadStatus();

    if (!auth.required || auth.authenticated) {
        return next();
    }

    return next({ name: 'Login', query: { redirect: to.fullPath } });
});

export default router;
