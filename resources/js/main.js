//require('./bootstrap');

import Vue from 'vue';
import App from './App';
import router from './router';

new Vue({
    el: '#app',
    router,
    template: '<App/>',
    render: (h) => h(App),
});
