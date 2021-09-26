import Vue from 'vue';
import Router from 'vue-router';
import Dashboard from '../Views/Dashboard';
import Settings from '../Views/Settings';
import Posters from '../Views/Posters';

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
];

let router = new Router({
    routes,
});

export default router;
