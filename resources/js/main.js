//require('./bootstrap');

import Vue from 'vue';
import App from './App';
import router from './router';

window.app = new Vue({
    el: '#app',
    router,
    template: '<App/>',
    render: (h) => h(App),
});
