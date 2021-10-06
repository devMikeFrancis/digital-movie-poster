import Vue from 'vue';
import Router from 'vue-router';
import Dashboard from '../Views/Dashboard';
import Settings from '../Views/Settings';
import Posters from '../Views/Posters';
import PostersEdit from '../Views/PostersEdit';
import Voting from '../Views/Voting';

Vue.use(Router);

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
];

let router = new Router({
    mode: 'history',
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
