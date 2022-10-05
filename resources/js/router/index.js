import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../Views/Dashboard.vue';
import Settings from '../Views/Settings.vue';
import Posters from '../Views/Posters.vue';
import PostersEdit from '../Views/PostersEdit.vue';
import Voting from '../Views/Voting.vue';
import About from '../Views/About.vue';

const routes = [
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: {
            requiresSetup: true,
        },
    },
    {
        path: '/settings',
        name: 'Settings',
        component: Settings,
    },
    {
        path: '/posters',
        name: 'Posters',
        component: Posters,
    },
    {
        path: '/posters/:id',
        name: 'PostersEdit',
        component: PostersEdit,
    },
    {
        path: '/voting',
        name: 'Voting',
        component: Voting,
    },
    {
        path: '/about',
        name: 'About',
        component: About,
    },
];

let router = createRouter({
    history: createWebHistory('/'),
    linkExactActiveClass: 'active',
    routes,
});

router.beforeEach((to, from, next) => {
    if (from.path === '/' && from.name !== null) {
        clearInterval(window.transitionImagesInterval);
        if (window.audio) {
            window.audio.pause();
            window.audio = null;
        }
    }
    next();
});

export default router;
